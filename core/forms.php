<?php

namespace engine\core;

class forms
{
	protected $template;
	
	protected $primary_id = 'id';

	public $table_name; //имя таблицы
	public $table_row_id; //идентификатор строки в таблице (id)
	public $U_ACTION; //submitter в режиме редактирования

	public $U_EDIT; //кнопка EDIT
	public $U_DEL; //кнопка DELETE

	public $additional_buttons; //дополнительные кнопки

	public $upload_folder; //имя папки в аплоадах

	public $ajax_checkbox = array();

	/* Таблицы и отображение
	----------------------------------------*/
	public $addButton = true; //существует ли действие "добавить новый"
	public $addButtonText = 'Добавить новый';

	public $titleTable = 'Просмотр';	//fixed by Vitaly - заголовок при выводе списка-таблицы
	public $alertError = '';			//fixed by Vitaly - если есть, то выводим ошибки

	/* Sort options
	----------------------------------------*/
	public $sortElements = false; //использовать ли сортировку
	public $htmlTableId = 'table-1'; //id таблицы


	#PAGINATOR
	public $paginator = false;
	public $paginator_code = '';
	public $paginator_onpage = 10;


	#TOOLTIP
	public $tooltip = false;

	protected $uploader; //класс загрузчика

	/**

	генерация шаблона "на лету"

	text
	array(name => 'title', 'title' => 'Title fielld', 'type' => 'text', 'value' => 'Default title')

	checkbox
	array('name' => 'visible', 'title' => 'Checkbox fielld', 'type' => 'checkbox', 'value' => 1, 'checked' => true)

	textarea
	array('name' => 'preview', 'title' => 'Textarea fielld', 'type' => 'textarea', 'value' => 'Default text', 'width' => 300, 'css' => 'index.css', 'editor' => 'basic')

	select
	array('name' => 'preview', 'title' => 'Textarea fielld', 'type' => 'select', 'options' => array('first' => 1, 'second' => 2), 'value' => '1233data')

	file
	array('name' => 'preview', 'title' => 'Textarea fielld', 'type' => 'file', 'old' => 'old_file', 'value' => 'image1.jpg')

	image
	array('name' => 'image', 'title' => 'Image', 'type' => 'file', 'old' => 'old_image', 'value' => 'image1.jpg', 'size' => array(BIG_WIDTH, BIG_HEIGHT, MIN_WIDTH, MIN_HEIGHT))

*/
	function __construct()
	{
		global $template;
		
		$this->template =& $template;
	}

	public function add_ajax_checkbox($value)
	{
		$this->ajax_checkbox[] = $value;
	}

	public function createEditFields($fields_array)
	{
		global $user;
		
		$output = '';
	
		foreach( $fields_array as $key => $data )
		{
			$this->template->assign('input', $data);
			
			if( (isset($data['perms']) && in_array($user->group, $data['perms'])) || !isset($data['perms']) )
			{
				switch( $data['type'] )
				{
					case 'text':     $output .= $this->createInputText(); break;
					case 'textbig':  $output .= $this->createInputTextBig(); break;
					case 'checkbox': $output .= $this->createInputCheckbox(); break;
					case 'textarea': $output .= $this->createTextarea(); break;
					case 'file':     $output .= $this->createInputFile($data); break;
					case 'select':   $output .= $this->createInputSelect(); break;
					case 'date':     $output .= $this->createInputDate(); break;
					case 'code':     $output .= $data['html']; break;
					case 'button':   $output .= $this->createInputButton(); break;
					case 'noinput':  $output .= $this->createNoInput(); break;
					case 'modifyurl':
					case 'hidden':
					
						$output .= $this->createInputHidden();
						
					break;
				}
			}
			else
			{
				$output .= $this->createInputHidden($data);
			}
		}
	
		return $output;
	}

	//добавление кнопки
	function addAdditionalButton($path, $name, $plus)
	{
		$button = '<input class="button1" style="width:100%;" type="button" value="'.$value.'" onclick="Redirect(arguments, \''.$path.'\');" />
				<br />';
		$this->additional_buttons($button);
	}

	//экспорт формы в шаблон и вывод на экран
	public function createEditTMP($fields)
	{
		$this->template->assign(array(
			'FIELDS'   => $this->createEditFields($fields),
			'U_ACTION' => $this->U_ACTION
		));

		$this->template->file = 'acp_EditTMP.html';
	}

	/**
	* Сохранение данных формы
	*/
	public function saveIntoDB(array $fieldset)
	{
		global $app, $request, $user;

		if( empty($fieldset) )
		{
			return false;
		}
		
		$sql_ary = array();

		foreach( $fieldset as $key => $data )
		{
			if( in_array($data['type'], array('code', 'button', 'noinput')) )
			{
				continue;
			}
			
			//внесение в БД тестовых полей
			if( $data['type'] != 'file' && $data['type'] != 'date' && $data['name'] != 'modifyurl' )
			{
				if( (isset($data['perms']) && in_array($user->group, $data['perms'])) || !isset($data['perms']) )
				{ 
					$value = $request->post($data['name'], '');
	
					$sql_ary[$data['name']] = ($data['type'] == 'textarea') ? htmlspecialchars_decode($value) : $value;
				}
			}

			//превращение времени в mktime
			if( $data['type'] == 'date' )
			{
				$value = $request->post($data['name'], '');
				preg_match("/([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})/iu", $value, $reg);
				if (isset($reg[1]) && isset($reg[2]) && isset($reg[3]))
				{
					$reg[1] = intval($reg[1]);
					$reg[2] = intval($reg[2]);
					$reg[3] = intval($reg[3]);
				
					if ($reg[1] > 0 && $reg[2] > 0 && $reg[2] < 13 && $reg[3] > 2000 && $reg[3] <= date("Y"))
					{
						$dh = date('h');
						$di = date('i');
						$ds = date('s');
						if (isset($data['old']))
						{
							if (date('j',$data['old']) == $reg[1] && 
								date('n',$data['old']) == $reg[2] && 
								date('Y',$data['old']) == $reg[3])
							{
								$t = $data['old'];
							}
							else 
							{
								$t = mktime (date('h'), date('i'), date('s'), $reg[2], $reg[1], $reg[3]);
							}
						}
						else
						{
							$t = mktime (date('h'), date('i'), date('s'), $reg[2], $reg[1], $reg[3]);
						}
					
						$sql_ary[$data['name']] = $t;
					}
				}
			}

			//обработка файла или фотографий
			if($data['type'] == 'file')
			{
				if( !$this->uploader )
				{
					$this->uploader = new \engine\core\upload();
				}
			
				//ресаизим и перемещаем изображение
				if($data['old'] == 'old_image')
				{
					$this->uploader->input_name = $data['name'];
					$this->uploader->max_file_size = 6 * 1024 * 1024;
					$this->uploader->pic_width_max = 6000;
					$this->uploader->pic_height_max = 6000;
					$this->uploader->watermark_pos_x = "right";
					$this->uploader->watermark_pos_y = "bottom";
					$this->uploader->watermark_delta = 0;
		

					//!!!!!!!!!!!!!!! ИСПРАВЛЕНО !!!!!!!!!!!!!!!!!!
					//Нужно проверять $_FILES[$data['name']]['name']
					//а не $_FILES[$data['name']] - этот массив всегда есть
					//Условие никогда не будет выполнено
					if(empty($_FILES[$data['name']]['name']))
					{
						$filename = @$data['value'];
					}
					else
					{
						if($this->uploader->CheckFile())
						{
							if(isset($_POST['modifyurl']))
							{
								//$filename = md5(mktime()).'.'.$this->uploader->GetExtension();
								$filename_noext = modifyUrl($_POST['title']).'-'.$this->table_row_id.substr(md5(time().'солька'), 0 ,3);
								$filename = $filename_noext.'.'.$this->uploader->GetExtension();
							}
							else
							{
								$filename_noext = md5(mktime());
								$filename = $filename_noext.'.'.$this->uploader->GetExtension();
							}

							# ЕСЛИ НЕТ РАСШИРЕННЫХ НАСТРОЕК ЗАГРУЗКИ ИЗОБРАЖЕНИЙ
							if (!(isset($data['resize']) && is_array($data['resize'])))
							{
								/* Delete existing items
								--------------------------------------------*/
								if(file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$data['value']))
									@unlink(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$data['value']);
	
		                        if(file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/sm/'.$data['value']))
									@unlink(SITE_DIR.'uploads/'.$this->upload_folder.'/sm/'.$data['value']);
	
								if(file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/original/'.$data['value']))
									@unlink(SITE_DIR.'uploads/'.$this->upload_folder.'/original/'.$data['value']);
	
								//если размеры переданы, то используем их
								if(isset($data['size']) && is_array($data['size']))
								{
									$bigWidth = isset($data['size'][0])?$data['size'][0]:1024;
									$bigHeight = isset($data['size'][1])?$data['size'][1]:768;
	
									$prevWidth = isset($data['size'][2])?$data['size'][2]:300;
									$prevHeight = isset($data['size'][3])?$data['size'][3]:'';
								}
								else
								{
									$bigWidth = 1024;
									$bigHeight = 768;
	
									$prevWidth = 300;
									$prevHeight = '';
								}
	
								$is_watermark = true;
								if (isset($data['watermark']))
									$is_watermark = $data['watermark'];
							
								//original folder
								$this->uploader->dir_dest = SITE_DIR.'uploads/'.$this->upload_folder.'/original/'; 
								if (!is_dir($this->uploader->dir_dest))
									mkdir($this->uploader->dir_dest, 0777, true);
								$this->uploader->ImageResized($bigWidth, $bigHeight, $filename, true, false);
								//main upload folder
								$this->uploader->dir_dest = SITE_DIR.'uploads/'.$this->upload_folder.'/'; 
								if (!is_dir($this->uploader->dir_dest))
									mkdir($this->uploader->dir_dest, 0777, true);
								$this->uploader->ImageResized($bigWidth, $bigHeight, $filename, true, $is_watermark);
								//mini upload folder
								$this->uploader->dir_dest = SITE_DIR.'uploads/'.$this->upload_folder.'/sm/'; 
								if (!is_dir($this->uploader->dir_dest))
									mkdir($this->uploader->dir_dest, 0777, true);
								$this->uploader->ImageResized($prevWidth, $prevHeight, $filename, true, $is_watermark);
							}
							else 
							{
								# загружаем фотку, в зависимости от переданных настроек
								$resize = $data['resize'];
								# удаляем старые фотки
								foreach ($resize AS $dir => $settings)
								{
									$dir_tmp = trim($dir,'/');
									if ($dir_tmp == 'root')
										$dir_tmp = '';
									else 
									{
										$dir_tmp .= '/';
										# если папки нету, создаем ее
										if (!is_dir(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_tmp))
											mkdir(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_tmp, 0777, true);
									
									}
									
									# удаляем старую фотку из данной категории
									if(file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_tmp.$data['value']))
										@unlink(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_tmp.$data['value']);
								}
							
								#загружаем фоту
								foreach ($resize AS $dir => $settings)
								{
									$w = intval($settings[0]);
									$h = intval($settings[1]);
									$watermark = (is_bool($settings[2])) ? $settings[2] : false;
									$cropped = (is_bool($settings[3])) ? $settings[3] : false;
									$trim = (is_bool($settings[4])) ? $settings[4] : false;
								
									$dir_tmp = trim($dir,'/');
									if ($dir_tmp == 'root')
										$dir_tmp = '';
									else 
										$dir_tmp .= '/';
									$this->uploader->dir_dest = SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_tmp;
									if ($cropped)
										$this->uploader->CroppedImageResized($w, $h, $filename_noext);
									else
										$this->uploader->ImageResized($w, $h, $filename, true, $watermark, $trim);
								}
							
							}
						}
						else
							$filename = @$data['value'];
					}

				}

				//перемещаем файл
				if($data['old'] == 'old_file')
				{
					if (!empty($_FILES[$data['name']]['name']))		
					{		
						//удаляем старый файл
						if (isset($_POST['old_'.$data['name']]) && $_POST['old_'.$data['name']] != '')
							if (file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$_POST['old_'.$data['name']]))
								unlink(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$_POST['old_'.$data['name']]);			
				
						$this->uploader->input_name = $data['name'];
				
						//загружаем новый
						if(isset($_POST['modifyurl']) && isset($_POST['title']))
							$filename = modifyUrl($_POST['title']).'-'.$this->table_row_id.'.'.$this->uploader->GetExtension();
						else
							$filename = md5(mktime()).'.'.$this->uploader->GetExtension();
							
						$this->uploader->dir_dest = SITE_DIR.'uploads/'.$this->upload_folder.'/'; 
						if (!is_dir($this->uploader->dir_dest))
							mkdir($this->uploader->dir_dest, "0777", true);
							
						$this->uploader->Save($filename);	
					}
					else 
					{
						$filename = @$data['value'];
					}
				}

				$sql_ary[$data['name']] = $filename;
			}
		}
		
		if( $request->is_set_post('title') && $request->is_set_post('modifyurl') )
		{
			$sql_ary['modifyurl'] = modifyUrl($request->post('title', '') . ' ' . $this->table_row_id);
		}
		
		$sql = '
			UPDATE
				' . $this->table_name . '
			SET
				' . $app['db']->build_array('UPDATE', $sql_ary) . '
			WHERE
				' . $this->primary_id . ' = ' . $app['db']->check_value($this->table_row_id);
		$app['db']->query($sql);
		
		return true;
	}


	/**

	$data
	array('id' => 1, 'title' => 'jkfjk', 'desc' => 'here is desc', 'image' => '')

	$titles
	array('ID', НАЗВАНИЕ, ОПИСАНИЕ и тд
*/
	//создание шаблона для листинга
	function createShowMode($titles, $data = '', $width = array())
	{
		//создаем заголовки
		$head = '<thead><tr class="nodrop nodrag">';
		
		foreach($titles as $key => $field)
		{
			$w = "";
			if (isset($width[$key]) && $width[$key] != "")
				$w = ' style="width:'.intval($width[$key]).'px"';
			$head .= '<th'.$w.'>'.$field.'</th>';
		}

		$head .= '<th width="90px">Управление</th></tr></thead>';

		//создаем содержимое
		$i = 1;
		$count = count($data);
		$cols = '';

		//массив данных пуст?
		if(empty($data)) return $head;
		
		$cols .= '<tbody>';
		
		foreach($data as $key => $value) //крутим весь массив
		{
			if(!empty($value))
			{

				if (count($value) == 2 && isset($value[0]) && $value[0] == 'tr_union_td')
				{
					$cols .= '<tr>';
					$cols .= '<th colspan="'.(count($titles) + 1).'" style="text-align:center;">'.$value[1].'</th>';
					$cols .= '</tr>';
					continue;
				}
			
				if($this->sortElements && !isset($value['sort']))
					exit('Sort field does not exist! Check you SQL query');
			
				//необходима сортировка - ТОЛЬКО при наличии поля sort)) которое мы, кстати, не показываем)
				if(isset($value['sort']) && isset($value[$this->primary_id]) && $this->sortElements)
				{
					$sortId = ' id="table-row-'.$value[$this->primary_id].'"';  //добавляем к полю SORT id - необходимо для работы с jQuery
					unset($value['sort']);  //удаляем поле SORT чтобы не показывать его
				}
				else
					$sortId = '';

				$style_tr = '';
				if (isset($value['style_tr']))
				{
					$style_tr = ' style="'.$value['style_tr'].'"';
					unset($value['style_tr']);
				}
				
				$row = ' class="row'.($i%2+1).'" '.$sortId . $style_tr;
				$cols .= '<tr '.$row. (!empty($this->U_EDIT) ? ' ondblclick="Redirect(arguments, \''.str_replace('\\', '\\\\', $this->U_EDIT).$value[$this->primary_id].'\');"' : '') . '>';
			

				//добавляем данные
				foreach($value as $td_name => $td_data)
				{
					if ($td_name == $this->primary_id && $this->sortElements)
					{
						$cols .= '<td class="dragHandle" style="text-align: center;">' . $td_data . '<br><img src="images/grid_dot.png" alt="" style="margin-top: 4px;"></td>';
					}
					elseif( $td_name == $this->primary_id )
					{
						$cols .= '<td style="text-align: center;">' . $td_data . '</td>';
					}
					elseif($td_name == 'date')
						$cols .= '<td>'.date('d.m.Y', $td_data).'</td>';
					//ajax - checkbox'ы
					elseif (in_array($td_name, $this->ajax_checkbox))
					{
						$checked = '';
						if (intval($td_data))
							$checked = ' checked="checked"';
						$cols .= '<td style="text-align:center"><input type="checkbox" value="1" onclick="AjaxClickCheckbox(this, \''.$td_name.'\', \''.$this->table_name.'\', \''.$value[$this->primary_id].'\')" '.$checked.'/></td>';
					}
					elseif($td_name == 'status')
					{
						if($td_data == 0)
							$cols .= '<td style="background: #9fff95;">Не обработан</td>';
						elseif($td_data == 1)
							$cols .= '<td style="background: #fedcdc;">OK</td>';
						else
							$cols .= '<td>'.$td_data.'</td>';	
					}
					else
					{
						if($td_name != 'add_buttons')
							$cols .= '<td>'.$td_data.'</td>';
					}
				}

				//кнопка редактирования
				if(!empty($this->U_EDIT))
				{
					$cols .= '<td>
						<input class="button1" style="width:100%;" type="button" value="Изменить" onclick="Redirect(arguments, \''.str_replace('\\', '\\\\', $this->U_EDIT).$value[$this->primary_id].'\');" />
						<br />';
				}

				//если есть дополнительные кнопки, добавляем их
				if(!empty($this->additional_buttons))
					$cols .= $this->additional_buttons;
			
				if(isset($value['add_buttons']) && !empty($value['add_buttons']))
				{
					foreach($value['add_buttons'] as $button)
						$cols .= $button;
				}

				//кнопка удаления
				if(!empty($this->U_DEL))
				{
					$cols .= '<input class="button1" style="width:100%;" type="button" value="Удалить" onclick="if(confirm(\'Будет удалена вся информация связанная с этой записью! Продолжить?\')) {Redirect(arguments, \''.$this->U_DEL.$value[$this->primary_id].'\');}" />
						</td>';
				}



				$cols .= '</tr>';
			}
		$i++;
		}

		$cols .= '</tbody>';
		
		$tableInnerHtml = $head.$cols;
		return 	$tableInnerHtml;

	}

	//скрипт, обеспечивающий сортировку строк в таблице
	protected function createSort()
	{

		$script = '
		<script type="text/javascript">
		$(document).ready(function() {
		    // Initialise the table
			$("#'.$this->htmlTableId.'").tableDnD({
	        	onDrop: function(table, row) {
		        	$("#'.$this->htmlTableId.' tbody tr").each(function(i){
						$(this).removeClass("row1 row2").addClass((i % 2) ? "row1" : "row2");
					});
					
					$.ajax({
					   type: "POST",
					   url: "includes/ajax/changesort.php",
					   data: ({ table: "'.$this->table_name.'", vorders: $("#'.$this->htmlTableId.'").tableDnDSerialize() })
					});
	       	},
	        onDragClass: "myDragClass",
		    dragHandle: "dragHandle"
	    	});
			
			$(".dragHandle").css("cursor", "move");
		});
	    </script>';
	
		return $script; 
	}

	//экспорт содержимого в шаблон и вывод на экран
	function createShowTMP($titles, $table_data='', $width=array())
	{
		$table = $this->createShowMode($titles, $table_data, $width);
	
		$this->template->file = 'acp_ShowTMP.html';
		$this->template->assign(array(
			'HTMLTABLEID'	=> $this->htmlTableId, //id таблицы
			'SORTINGSCRIPT'	=> !$this->sortElements?'':$this->createSort(), //скрипт сортировки
			'TABLE'			=> $table, //поля таблицы
			'PAGINATOR'		=> $this->paginator_code, //Paginator
			'TOOLTIP'		=> $this->tooltip, //Tooltip
			'ADDBUTTON'     => $this->addButton, //нужна ли кнопка ДОБАВИТЬ
			'ADDBUTTONTEXT'	=> $this->addButtonText, //текст кнопки ДОБАВИТЬ
			'TITLETABLE'	=> $this->titleTable, //заголовок таблицы
			'FILTERSLIST'	=> $this->GenerateFilterList() //выводим фильтры, если они добавлены
		));
		if (trim($this->alertError) != "")
			$this->template->assign('ERROR', trim($this->alertError));
	}

	public function get_page()
	{
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1;
		if ($p < 1) $p = 1;
		return $p;	
	}

	/*--------------- NEW METHOD -------------------*/
	/*--------------- 12.01.2011 -------------------*/
	/*----------- Добавление фильтра----------------*/

	public $FiltersList = array();
	//добавляем фильтр к таблице вывода
	public function AddFilter($List, $url='', $get_param='', $get_param_val=0, $title_select='Фильтр:')
	{
		$this->FiltersList[] = array(
			'list' 			=> $List,
			'url'			=> $url,
			'get_param'		=> $get_param,
			'get_param_val'	=> $get_param_val,
			'title' 		=> $title_select
		);
	}
	public function GenerateFilterList()
	{
		$text = '';
		if (count($this->FiltersList))
		{
			$script_js_ready = '<script type="text/javascript">
			$(document).ready(function() {';
		
			foreach ($this->FiltersList AS $filter)
			{
				if (!is_array($filter['list']) || count($filter['list']) == 0)
					continue;
				if ($filter['get_param'] == '')
					continue;
				
				//генерим select
				$select = '';
				$select .= $filter['title'] . ' <select id="'.$filter['get_param'].'">';
				foreach ($filter['list'] AS $fid => $fname)
				{
					$selected = '';
					if ($fid == $filter['get_param_val'])
						$selected = ' selected="selected"';
					$select .= '<option value="'.$fid.'"'.$selected.'>'.$fname.'</option>';
				}
				$select .= '</select>';
			
				//добавляем событие на смену фильтра
				$script_js_ready .= '
					$(\'#'.$filter['get_param'].'\').change(function(){
						var param = $(\'#'.$filter['get_param'].'\').val();
						var url = "'.$filter['url'].'";
						url = url + "&'.$filter['get_param'].'=" + param;
						window.location.assign(url);
					});
				';
			
				//накапливаем фильтры
				$text .= '<div style="text-align: right; margin: 5px 0 10px 0;">'.$select.'</div>';
			}
		
			$script_js_ready .= '});
	        </script>';
		
			$text = $script_js_ready . $text;
		}
		return $text;
	}
	
	public function set_primary_id($primary_id)
	{
		$this->primary_id = $primary_id;
	}
	
	/**
	* Кнопка
	*/
	protected function createInputButton()
	{
		return $this->template->fetch('forms/button.html');
	}

	/**
	* Флажок
	*/
	protected function createInputCheckbox()
	{
		return $this->template->fetch('forms/checkbox.html');
	}

	/**
	* Виджет выбора даты
	*/
	protected function createInputDate()
	{
		static $plugin_loaded;
		
		$this->template->assign('date_plugin_loaded', $plugin_loaded);
		
		if( !$plugin_loaded )
		{
			$plugin_loaded = true;
		}
		
		return $this->template->fetch('forms/date.html');
	}

	/**
	* Файл
	*/
	protected function createInputFile($data)
	{
		$ajax_id = 'dl'.substr(md5($data['name']),0,5);
	
		$img_width = 120;
		if (isset($data['img_width']))
			$img_width = $data['img_width'];
		
		if($data['old'] == 'old_image')
			$dir_preview = 'sm/';
		else 
			$dir_preview = '';
		if (isset($data['dir_preview']))
		{
			$dir_preview = $data['dir_preview'];
			$dir_preview = rtrim($dir_preview, '/') . '/';
		}
		
	
		$input = '<dl>
			<dt><label for="'.$data['name'].'">'.$data['title'].':</label></dt>
			<dd>
			<input type="file" name="'.$data['name'].'" id="'.$data['name'].'" />
			<input type="hidden" name="old_'.$data['name'].'" id="old_'.$data['name'].'" value="'.$data['value'].'" />
			</dd>
			</dl>';

		$old = '';
		//показываем старый файл
		if(!empty($data['value']) && file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value']))
		{
			$file_exist = false;
		
			$old .= '<div id="filebox-'.$data['name'].'">';
			//изображение со старым old_image
			if($data['old'] == 'old_image')
			{
				if (file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value']))
				{
					$im_info = getimagesize(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value']);
					if (isset($im_info[0]) && $im_info[0] < $img_width)
						$img_width = $im_info[0];
				
					$old .= '<img src="/uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value'].'" width="'.$img_width.'" style="border: 1px solid #777; padding: 1px; margin-right:10px; float:left;" />';
					$file_exist = true;
				}
				else
					$old .= '<span style="color:red">Ошибка: Изображение отсутствует на диске!</span>';
			}
			//файл, либо любое другое поле old?
			else
			{
				if (file_exists(SITE_DIR.'uploads/'.$this->upload_folder.'/'.$data['value']))
				{
					$filename = $data['value'];
					$ext = strtolower(substr($filename, strrpos($filename, '.')+1));
					$file_exist = true;
				
					$image_format = array('jpg', 'jpeg', 'gif', 'png');
					$swf_format = array('swf');
				
					//показать изображение
					if (in_array($ext, $image_format))
					{
						$old .= '<img src="/uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value'].'" width="'.$img_width.'" style="border: 1px solid #777; padding: 1px; margin-right:10px; float:left;" />';
					}
					elseif (in_array($ext, $swf_format))
					{
						$old .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="'.$img_width.'">
									  <param name="movie" value="/uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value'].'" />
									  <param name="quality" value="high" />
									  <param name="wmode" value="opaque" />
									  <embed src="/uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value'].'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="'.$img_width.'" wmode="opaque"></embed>
								</object>';
					}	
					else 
					{			
						$old .= '<div style="float: left; padding-right: 10px;"><a href="/uploads/'.$this->upload_folder.'/'.$dir_preview.$data['value'].'" target="_blank">'.$data['value'].'</a></div>';
					}
					$file_exist = true;
				}
				else
					$old .= '<span style="color:red">Ошибка: Файл отсутствует на диске!</span>';
			}
			$old .= '</div>';
		
		
		if (isset($data['ajax_delete']) && $file_exist)
			{
				$old .= '
				<script type="text/javascript">
				$(document).ready(function() {
					$("#butloading-'.$data['name'].'").hide();
					$("#delete_element-'.$data['name'].'").click(function(){
						$.ajax({
					    	url: "'.$data['ajax_delete']['url'].'",
							type: "post",
							data: ('.$data['ajax_delete']['param'].'),
						    dataType: "html",
						    beforeSend: function(){
								$("#butdelete-'.$data['name'].'").hide();
								$("#butloading-'.$data['name'].'").show();
							},
						    success: function(answ){
						    	//alert(answ);
						    	if (answ == "success") 
						    	{ 
						    		$("#filebox-'.$data['name'].'").parent().parent().hide();
									$("#old_' . $data['name'] . '").val("");
						    	}
						    	else 
						    	{ 
						    		$("#butdelete-'.$data['name'].'").show();
									$("#butloading-'.$data['name'].'").hide();
						    	}
							},
							error: function ( xhr, ajaxOptions, thrownError ) {
								$("#butdelete-'.$data['name'].'").show();
								$("#butloading-'.$data['name'].'").hide();
							}
		    			});
					});
					});
	    		</script>
	    		';
			
				$old .= '<div id="butdelete-'.$data['name'].'"><img src="images/cross_script.png" title="Удалить фото/файл" id="delete_element-'.$data['name'].'" style="cursor: pointer;"></div>';
				$old .= '<div id="butloading-'.$data['name'].'"><img src="images/loading.gif" title="Подождите... Идет удаление файла"></div>';
				$old .= '<div style="font-size:1px; clear: left;"></div>';
			}	
			
			
			$input .= '<dl id="'.$ajax_id.'">
				<dt><label for="">Старый файл:</label></dt>
				<dd>
				'.$old.'
				</dd>
				</dl>';
		}

		return $input;
	}

	/**
	* Скрытое поле
	*/
	protected function createInputHidden()
	{
		return $this->template->fetch('forms/hidden.html');
	}

	/**
	* Список
	*/
	protected function createInputSelect()
	{
		return $this->template->fetch('forms/select.html');
	}

	/**
	* Текстовое поле
	*/
	protected function createInputText()
	{
		return $this->template->fetch('forms/text.html');
	}

	/**
	* Большое текстовое поле
	*/
	protected function createInputTextBig()
	{
		return $this->template->fetch('forms/textbig.html');
	}

	/**
	* Поле только для чтения
	*/
	protected function createNoInput()
	{
		return $this->template->fetch('forms/noinput.html');
	}

	/**
	* Текстовая область
	*/
	protected function createTextarea()
	{
		static $plugin_loaded;
		
		$this->template->assign('editor_plugin_loaded', $plugin_loaded);
		
		if( !$plugin_loaded )
		{
			$plugin_loaded = true;
		}
		
		return $this->template->fetch('forms/textarea.html');
	}
}
