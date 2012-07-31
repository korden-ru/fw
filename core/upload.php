<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\core;

class upload
{
	public 	$input_name;		//Имя элемента формы <input type="File" />
	private $ext = array();		//Массив разрешенных расширений для загрузки
	public  $dir_dest;			//Директория, куда сохраняем все загружаемые файлы
	public  $max_file_size;		//Максимальный размер загружаемого файла в байтах
	public  $pic_width_max;		//Максимальная ширина загружаемого изображения
	public  $pic_height_max;	//Максимальная высота загружаемого изображения
	
	public  $watermark_pos_x;	//Позиция подписи по оси х (left | center | right)
	public  $watermark_pos_y;	//Позиция подписи по оси y (top  | center | bottom)
	public  $watermark_path;	//Путь к подписи
	public  $watermark_path_sm;	//Путь к подписи
	public  $watermark_delta;	//растояние подписи от краев изображения
	
	public  $quality;			//Качество уменьшенной копии изображения (1 - 100)
	public  $back_color;		//Цвет подложки уменьшенной копии изображения
	
	public  $debug = false;		//показывать ли ошибки
	
	function __construct()
	{
		$this->input_name = "";
		$this->max_file_size = 5 * 1024 * 1024; //5Mb
		$this->pic_width_max = 6000;			//6000 пкс. Максимальная ширина загружаемого изображения
		$this->pic_height_max = 6000;			//6000 пкс. Максимальная высота загружаемого изображения
		
		$this->watermark_path = rtrim($_SERVER['DOCUMENT_ROOT'],"/").'/watermark.png';
		$this->watermark_path_sm = rtrim($_SERVER['DOCUMENT_ROOT'],"/").'/watermark_sm.png';
		$this->watermark_pos_x = "center";
		$this->watermark_pos_y = "center";
		$this->watermark_delta = 20;
		$this->quality = 90;
		$this->back_color = "#FFFFFF";
		
		$this->dir_dest = rtrim($_SERVER['DOCUMENT_ROOT'],"/").'/uploads/';
		
		$this->InitExtensions(); 
	}
	
	//загрузка доступных расширений
	function InitExtensions()
	{
		$this->ext[] = array("mime_type" => "image/jpeg");
		$this->ext[] = array("mime_type" => "image/pjpeg");
		$this->ext[] = array("mime_type" => "image/gif");
		$this->ext[] = array("mime_type" => "image/png");
		$this->ext[] = array("mime_type" => "application/octet-stream");
		return true;
	}
	
	/**
	 * @name GetPropertyFile
	 * Фунция возвращает свойства загружаемого файла
	 *
	 * @return array массив_свойств или ""
	 */
	function GetPropertyFile()
	{

		if ($this->input_name == "")
			return "";
		if (!(isset($_FILES[$this->input_name]['tmp_name']) && $_FILES[$this->input_name]['tmp_name'] != ""))
			return "";
			
		$prop = array(
			'name' 			=> $_FILES[$this->input_name]['name'],
			'type' 			=> $_FILES[$this->input_name]['type'],
			'tmp_name' 		=> $_FILES[$this->input_name]['tmp_name'],
			'error' 		=> $_FILES[$this->input_name]['error'],
			'size' 			=> $_FILES[$this->input_name]['size'],
			'image_width' 	=> 0,
			'image_height'	=> 0,
			'tag_atribute'	=> ""
		);
		if ($this->IsImage())
		{
			if ($im = getimagesize($_FILES[$this->input_name]['tmp_name']))
			{
				$prop['image_width'] = $im[0];
				$prop['image_height'] = $im[1];
				$prop['tag_atribute'] = $im[3];
			}
		}
			
		return $prop;
	}
	
	
	
	/**
	 * @name IsImage
	 * Фунция определяет является ли загружаемый файл изображением
	 *
	 * @return bool
	 */
	function IsImage()
	{
		if (substr_count(@$_FILES[$this->input_name]['type'], "image") > 0)
			return true;
		if (substr_count(@$_FILES[$this->input_name]['type'], "application/octet-stream") > 0)
			return true;
		return false;
	}
	
	/**
	 * @name GetExtension
	 * Функция возвращает расширение загружаемого файла
	 *
	 * @return string
	 */
	function GetExtension()
	{
		$name = strtolower($_FILES[$this->input_name]['name']);
		$ext = substr($name, strrpos($name, ".")+1);
		return $ext;
	}
	

	/**
	 * @name CheckFile
	 * Обработка ошибок загружаемого файла
	 *
	 * @return array коды_ошибок или ""
	 */
	function CheckFile()
	{
		$prop = $this->GetPropertyFile();
		$errors = array();
		
		//не получены свойства загружаемого файла
		if (!$prop)
		{
			$errors[] = 'upload_not_property';
			return $errors;
		}
		
		//проверка на загружаемый mime/type
		$flag_ext = false;
		foreach ($this->ext AS $val)
		{
			if ($val['mime_type'] == $_FILES[$this->input_name]['type'])
			{
				$flag_ext = true;
				break;
			}
		}
		if (!$flag_ext)
		{
			$errors[] = 'upload_not_ext';
			return $errors;
		}
		
		//проверка на допустимый размер файла (в байтах)
		if ($prop['size'] > $this->max_file_size)
		{
			$errors[] = 'upload_max_file_size';
			return $errors;
		}

		if ($prop['error'] != 0)
			$errors[] = 'upload_file_error';
		
		//если загружаемый файл - изображение
		if (preg_match("/^image/i", $_FILES[$this->input_name]['type']))
		{
			if ($prop['image_width'] == 0 || $prop['image_height'] == 0)
				$errors[] = 'upload_image_not_valid';
			if ($prop['image_width'] > $this->pic_width_max)
				$errors[] = 'upload_image_width_max';
			if ($prop['image_height'] > $this->pic_height_max)
				$errors[] = 'upload_image_height_max';
			
			if (count($errors)>0)
				return $errors;
		}
		
		return true;
	}
	
	//WATERMARK
	function create_watermark( $main_img_obj, $watermark_img_obj, $alpha_level = 100 )
	{
		if ($this->watermark_pos_x != "left" && $this->watermark_pos_x != "center" && $this->watermark_pos_x != "right")
			$this->watermark_pos_x = "right";
		if ($this->watermark_pos_y != "top" && $this->watermark_pos_y != "center" && $this->watermark_pos_y != "bottom")
			$this->watermark_pos_y = "bottom";
		
		$alpha_level /= 100; # переводим значение прозрачности альфа-канала из % в десятки
		# рассчет размеров изображения (ширина и высота)
		$main_img_obj_w = imagesx( $main_img_obj );
		$main_img_obj_h = imagesy( $main_img_obj );
		$watermark_img_obj_w = imagesx( $watermark_img_obj );
		$watermark_img_obj_h = imagesy( $watermark_img_obj );
		
		
		# определение координат центра изображения в зависимости от расположения подписи
		if ($this->watermark_pos_x == "left")
		{
			$main_img_obj_min_x = $this->watermark_delta;
			$main_img_obj_max_x = $this->watermark_delta + $watermark_img_obj_w;
		}
		elseif ($this->watermark_pos_x == "center")
		{
			$main_img_obj_min_x = round(($main_img_obj_w - $watermark_img_obj_w ) / 2);
			$main_img_obj_max_x = $main_img_obj_min_x + $watermark_img_obj_w;
		}
		else
		{
			$main_img_obj_min_x = $main_img_obj_w - $watermark_img_obj_w - $this->watermark_delta;
			$main_img_obj_max_x = $main_img_obj_w - $this->watermark_delta;
		}
		
		if ($this->watermark_pos_y == "top")
		{
			$main_img_obj_min_y = $this->watermark_delta;
			$main_img_obj_max_y = $this->watermark_delta + $watermark_img_obj_h;
		}
		elseif ($this->watermark_pos_y == "center")
		{
			$main_img_obj_min_y = round(($main_img_obj_h - $watermark_img_obj_h ) / 2);
			$main_img_obj_max_y = $main_img_obj_min_y + $watermark_img_obj_h;
		}
		else 
		{
			$main_img_obj_min_y = $main_img_obj_h - $watermark_img_obj_h - $this->watermark_delta;
			$main_img_obj_max_y = $main_img_obj_h - $this->watermark_delta;
		}
		
		imagecopy($main_img_obj, $watermark_img_obj, $main_img_obj_min_x, $main_img_obj_min_y, 0, 0, $watermark_img_obj_w, $watermark_img_obj_h);
		return $main_img_obj;
	}
	
	# усреднение двух цветов с учетом прозрачности альфа-канала
	function _get_ave_color( $color_a, $color_b, $alpha_level )
	{
		return round( ( ( $color_a * ( 1 - $alpha_level ) ) + ( $color_b * $alpha_level ) ) ); 
	}
	
	# возвращаем значения ближайших RGB-составляющих нового рисунка
	function _get_image_color($im, $r, $g, $b)
	{
		$c=imagecolorexact($im, $r, $g, $b);
		if ($c!=-1) return $c;
		$c=imagecolorallocate($im, $r, $g, $b);
		if ($c!=-1) return $c;
		return imagecolorclosest($im, $r, $g, $b);
	} 
	
	
	
	
	
	/**
	 * @name ImageResized
	 * Функция создает уменьшенную копию изображения
	 * Если не указан параметр $width или $height, то он считается автоматически
	 *
	 * @param int $width ширина уменьшенной копии
	 * @param int $height высота уменьшенной копии
	 * @param string $filename	имя файла
	 * @param bool $is_resized	делать ли автоматический ресайз
	 * @param bool $is_watermark накладывать ли подпись на изображение
	 * 
	 * @return bool
	 */
	function ImageResized ($width = 150, $height = "", $filename, $is_resized = true, $is_watermark = false, $trim = false)
	{
		
		$width = abs(intval($width));
		$height = abs(intval($height));
		$height = ( $height ) ?: $width;
		
		$prop = $this->GetPropertyFile();
		
		if ($width > $prop['image_width'] && $height > $prop['image_height'])
		{
			$this->Save($filename);
			return true;
		}
		
		if ($this->dir_dest != "")
			$filename = $this->dir_dest . $filename;

		if ($width == 0 && $height == 0)
			return false;
			
		//формат изображения
		$format = strtolower(substr($prop['type'], strpos($prop['type'], '/')+1));
		if ($format =="pjpeg") $format = "jpeg";
		
		if ($format == "octet-stream")
		{
			$format = $this->GetExtension();
			if ($format == "jpg")
				$format = "jpeg";
		}
		
		global $app;
		
		if( $format == 'gif' )
		{
			/* -sample не нарушает gif-анимацию */
			// @passthru(sprintf('%sconvert "%s" -sample %dx%d +profile "*" "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $prop['tmp_name'], $width, $height, $filename));
		}
		else
		{
			if( $trim )
			{
				@passthru(sprintf('%sconvert "%s" -quality %d -filter triangle -trim -resize %dx%d\> -gravity south -background None -extent %dx%d +repage "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $prop['tmp_name'], 75, $width, $height, $width, $height, $filename));
				
				return true;
			}
			else
			{
				// @passthru(sprintf('%sconvert -size %dx%d "%s" -quality %d -filter triangle -resize %dx%d\> +repage "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $width, $height, $prop['tmp_name'], 75, $width, $height, $filename));
			}
		}
		
		if ($width == 0 && $is_resized)
			$width = round($prop['image_width'] * $height / $prop['image_height']);
		elseif ($height == 0 && $is_resized)
			$height = round($prop['image_height'] * $width / $prop['image_width']);
			
		//подсчитываем необходимую ширину
		if ($width > $prop['image_width'] || $height > $prop['image_height'])
		{
			if ($width > $prop['image_width'])
			{
				if ($prop['image_height'] > $height)
				{
					$width = round($prop['image_width'] * $height / $prop['image_height']);
				}
				else 
				{
					$width = $prop['image_width'];
					$height = $prop['image_height'];
				}
			}
			else
			{
				if ($prop['image_width'] > $width)
				{
					$height = round($width * $prop['image_height'] / $prop['image_width']);
				}
				else 
				{
					$width = $prop['image_width'];
					$height = $prop['image_height'];
				}
			} 
		}
		else 
		{	
			if ($width == 0 && $is_resized)
				$width = round($prop['image_width'] * $height / $prop['image_height']);
			//подсчитываем необходимую высоту
			elseif ($height == 0 && $is_resized)
				$height = round($prop['image_height'] * $width / $prop['image_width']);
			elseif ($width <= $prop['image_width'] && $height != 0 && $is_resized)
				$height = round($prop['image_height'] * $width / $prop['image_width']);
			elseif ($height <= $prop['image_height'] && $width != 0 && $is_resized)
				$width = round($prop['image_width'] * $height / $prop['image_height']);
		}
			
		//формат изображения
		$format = strtolower(substr($prop['type'], strpos($prop['type'], '/')+1));
		if ($format =="pjpeg") $format = "jpeg";
		
		if ($format == "octet-stream")
		{
			$format = $this->GetExtension();
			if ($format == "jpg")
				$format = "jpeg";
		}
		
		// global $app;
		// 
		// if( $format == 'gif' )
		// {
		// 	/* -sample не нарушает gif-анимацию */
		// 	@passthru(sprintf('%sconvert "%s" -sample %dx%d +profile "*" "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $prop['tmp_name'], $width, $height, $filename));
		// }
		// else
		// {
		// 	@passthru(sprintf('%sconvert -size %dx%d "%s" -quality %d -filter triangle %s -resize %dx%d> +repage "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $width, $height, $prop['tmp_name'], 75, (($trim) ? '-trim' : ''), $width, $height, $filename));
		// 	printf('%sconvert -size %dx%d "%s" -quality %d -filter triangle %s -resize %dx%d> +repage "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $width, $height, $prop['tmp_name'], 75, (($trim) ? '-trim' : ''), $width, $height, $filename);
		// 	exit;
		// }
		// 
		// return true;
			
		//проверяем поддерживает ли библиотека GD соответствующие функции
		$icfunc = "imagecreatefrom" . $format;
		if (!function_exists($icfunc)) return false;
		
		//создаем изображение из загружаемого файла
		$img_upload = $icfunc($prop['tmp_name']);
			
		//создаем уменьшенную копию
	
		$img_thumb = imagecreatetruecolor($width, $height);
		
		if ($format == 'png' || $format == 'gif')
			$this->setTransparency($img_thumb, $img_upload); 
		else 
			@imagefill($img_thumb, 0, 0, $this->back_color);		//заливаем цветом подложки
		
		//непосредственно создание уменьшенной копии
		@imagecopyresampled($img_thumb, $img_upload, 0, 0, 0, 0, $width, $height, $prop['image_width'], $prop['image_height']);
		
		//создаем подпись
		if ($is_watermark)
		{
			//получаем путь к watermark	
			$puth = $this->watermark_path;
			//если необходимо сжимаем размеры watermarka
			if ($width <= 300)
			{
				$ratio = $width / 1024;
				$this->watermark_delta = floor($ratio * $this->watermark_delta);
				$puth = $this->watermark_path_sm;
			}
			
			$watermark_img_obj = @imagecreatefrompng($puth);
			$img_thumb = $this->create_watermark($img_thumb, $watermark_img_obj, 66);
		}
		
		//сохраняем уменьшенное изображение
		$flag_return = false;
		if ($format == "gif") 
		{
		    $flag_return = @imagegif ($img_thumb, $filename);
		}
		elseif ($format == "jpeg") 
		{
		    $flag_return = @imagejpeg ($img_thumb, $filename, $this->quality);
		}
		elseif ($format == "png") 
		{
		    $flag_return = @imagepng ($img_thumb, $filename);
		}
		
		@ImageDestroy($img_upload);
		@ImageDestroy($img_thumb);
		
		return $flag_return;
	}
	
	/**
	 * @name CroppedImageResized
	 * Функция создает уменьшенную копию изображения в указанных размерах
	 * Если картинка не подходит пропорциям, то она обрезается автоматически
	 *
	 * @param int $width ширина уменьшенной копии
	 * @param int $height высота уменьшенной копии
	 * @param string $filename	имя нового файла (пишется без расширения файла, например, imagefile)
	 * 
	 * @return bool
	 */
	function CroppedImageResized($width=300, $height=300, $filename, $ext=false)
	{
		$prop = $this->GetPropertyFile();
		
		if ($prop == "") return false;
		if (($width == 0 || $height == 0) || ($prop['image_width'] == 0 || $prop['image_height'] == 0))
			return false;
		
		if ($this->dir_dest != "")
			$filename = $this->dir_dest . $filename;
		if (!$ext)
			$filename .= ".".$this->GetExtension();
			
		//подсчитываем необходимое разрешение изображения
		$width_orig = $prop['image_width'];
		$height_orig = $prop['image_height'];
		$ratio_orig = $width_orig/$height_orig;
	   
	    //подсчитываем новые размеры уменьшенного изображения
		if ($width/$height > $ratio_orig) 
		{
	       $new_height = $width / $ratio_orig;
	       $new_width = $width;
	    } 
	    else 
	    {
	       $new_width = $height * $ratio_orig;
	       $new_height = $height;
	    }
	   
	    $x_mid = $new_width/2;  //центр по горизонтали
	    $y_mid = $new_height/2; //центр по вертикали
			
		//формат изображения
		$format = strtolower(substr($prop['type'], strpos($prop['type'], '/')+1));
		if ($format =="pjpeg") $format = "jpeg";
		
		if ($format == "octet-stream")
		{
			$format = $this->GetExtension();
			if ($format == "jpg")
				$format = "jpeg";
		}
		
		global $app;
		
		/* -size ускоряет создание превью, фильтр немного замыливает изображение */
		@passthru(sprintf('%sconvert -size %dx%d "%s" -quality %d -filter triangle -resize %dx%d\^ -gravity Center -crop %dx%d+0+0 +repage "%s"', escapeshellcmd($app['config']['imagemagick_dir']), $width, $height, $prop['tmp_name'], 75, $width, $height, $width, $height, $filename));
		
		return true;
		
		//проверяем поддерживает ли библиотека GD соответствующие функции
		$icfunc = "imagecreatefrom" . $format;
		if (!function_exists($icfunc)) return false;
		
		//создаем изображение из загружаемого файла
		$img_upload = $icfunc($prop['tmp_name']);
		
		//создаем уменьшенное изображение в пропорциях загружаемого изображения
		$process = imagecreatetruecolor(round($new_width), round($new_height));
		if ($format == 'png' || $format == 'gif')
			$this->setTransparency($process, $img_upload); 

		imagecopyresampled($process, $img_upload, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
   		$thumb = imagecreatetruecolor($width, $height);
    	if ($format == 'png' || $format == 'gif')
			$this->setTransparency($thumb, $process); 
    	imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($width/2)), ($y_mid-($height/2)), $width, $height, $width, $height);

	    //сохраняем изображение
		$flag_return = false;
		if ($format == "gif") 
		{
		    $flag_return = @imagegif ($thumb, $filename);
		}
		elseif ($format == "jpeg") 
		{
			$flag_return = @imagejpeg ($thumb, $filename, $this->quality);
		}
		elseif ($format == "png") 
		{
		    $flag_return = @imagepng ($thumb, $filename);
		}
		
		//очищаем память
		imagedestroy($process);
    	imagedestroy($img_upload);
		imagedestroy($thumb);
    	
		return $flag_return;
		
	}
	
	/**
	 * @name Save
	 * Сохранение любых загружаемых файлов, кроме изображений 
	 *
	 * @param string $filename имя файла 
	 * @return bool
	 */
	function Save($filename)
	{
		if ($this->dir_dest != "")
			$filename = $this->dir_dest . $filename;
		
		return @copy($_FILES[$this->input_name]['tmp_name'], $filename);
	}

	function setTransparency(&$new_image, $image_source)
	{
		$transparencyIndex = imagecolortransparent($image_source);
	    $transparencyColor = array('red' => 255, 'green' => 255, 'blue' => 255);
	       
	    if ($transparencyIndex >= 0)
	    	$transparencyColor = imagecolorsforindex($image_source, $transparencyIndex);   
	       
	    $transparencyIndex = imagecolorallocate($new_image, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
	    imagefill($new_image, 0, 0, $transparencyIndex);
	    imagecolortransparent($new_image, $transparencyIndex);
	}
}
