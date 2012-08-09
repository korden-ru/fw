<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

/**
 * Обрезает текст в кодировке UTF-8 до заданной длины,
 * причём последнее слово показывается целиком, а не обрывается на середине.
 * Html сущности корректно обрабатываются.
 *
 * @param   string|null     $s                текст в кодировке UTF-8
 * @param   int|null|digit  $maxlength        ограничение длины текста
 * @param   string          $continue         завершающая строка, которая будет вставлена после текста, если он обрежется
 * @param   bool|null       &$is_cutted       текст был обрезан?
 * @param   int|digit       $tail_min_length  если длина "хвоста", оставшегося после обрезки текста, меньше $tail_min_length,
 *                                            то текст возвращается без изменений
 * @return  string|null|bool                  returns FALSE if error occured
 */
function utf8_str_limit($s, $maxlength = null, $continue = "\xe2\x80\xa6", &$is_cutted = null, $tail_min_length = 20) #"\xe2\x80\xa6" = "&hellip;"
{
	if (is_null($s)) return $s;

	$is_cutted = false;
	if ($continue === null) $continue = "\xe2\x80\xa6";
	if (! $maxlength) $maxlength = 200;

	#оптимизация скорости:
	#{{{
	if (strlen($s) <= $maxlength) return $s;
	$s2 = str_replace("\r\n", '?', $s);
	$s2 = preg_replace('/&(?> [a-zA-Z][a-zA-Z\d]+
                               | \#(?> \d{1,4}
                                     | x[\da-fA-F]{2,4}
                                   )
                             );  # html сущности (&lt; &gt; &amp; &quot;)
                           /sxSX', '?', $s2);
	if (strlen($s2) <= $maxlength || mb_strlen($s2) <= $maxlength) return $s;
	#}}}

	
	$char_re = 				 '  [\x09\x0A\x0D\x20-\x7E]           # ASCII strict
                              # [\x00-\x7F]                       # ASCII non-strict (including control chars)
                              | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
                              |  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
                              | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                              |  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
                              |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                              | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
                              |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                             ';
	
	
	preg_match_all('/(?> \r\n   # переносы строк
                          | &(?> [a-zA-Z][a-zA-Z\d]+
                               | \#(?> \d{1,4}
                                     | x[\da-fA-F]{2,4}
                                   )
                             );  # html сущности (&lt; &gt; &amp; &quot;)
                          | ' . $char_re . '
                        )
                       /sxSX', $s, $m);
	#d($m);
	if (count($m[0]) <= $maxlength) return $s;

	$left = implode('', array_slice($m[0], 0, $maxlength));
	#из диапазона ASCII исключаем буквы, цифры, открывающие парные символы [a-zA-Z\d\(\{\[] и некоторые др. символы
	#нельзя вырезать в конце строки символ ";", т.к. он используются в сущностях &xxx;
	$left2 = rtrim($left, "\x00..\x28\x2A..\x2F\x3A\x3C..\x3E\x40\x5B\x5C\x5E..\x60\x7B\x7C\x7E\x7F");
	if (strlen($left) !== strlen($left2)) $return = $left2 . $continue;
	else
	{
		#добавляем остаток к обрезанному слову
		$right = implode('', array_slice($m[0], $maxlength));
		preg_match('/^(?> [a-zA-Z\d\)\]\}\-\.:]+  #английские буквы или цифры, закрывающие парные символы, дефис для составных слов, дата, время, IP-адреса, URL типа www.ya.ru:80!
                           | \xe2\x80[\x9d\x99]|\xc2\xbb|\xe2\x80\x9c  #закрывающие кавычки
                           | \xc3[\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]|\xc4[\x9f\xb1\x9e\xb0]|\xc5[\x9f\x9e]  #турецкие
                           | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]   #русские буквы
                           | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]  #татарские
                         )+
                       /sxSX', $right, $m);
		#d($m);
		$right = isset($m[0]) ? rtrim($m[0], '.-') : '';
		$return = $left . $right;
		if (strlen($return) !== strlen($s)) $return .= $continue;
	}
	if (mb_strlen($s) - mb_strlen($return) < $tail_min_length) return $s;

	$is_cutted = true;
	return $return;
}


function hidden_fields($row)
{
	$string = '';
	foreach( $row as $key => $value )
	{
		$string .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\n";
	}
	return $string;
}

function debug($array, $exit=1)
{
	if (isset($_GET['admin']) || isset($_COOKIE['debug_mode']) || $_SERVER['REMOTE_ADDR'] == '79.175.20.190' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1')
	{
		echo '<pre>';
		print_r($array);
		echo '</pre>';
		if ($exit) exit();
	}
}

function check_email($email) 
{
    return preg_match("%^[A-Za-z0-9](([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z])+$%", $email); 
}


function modifyUrl($title)
{
	$title = htmlspecialchars_decode($title);
    $ruTitle = preg_replace('/[^А-Яа-яA-Za-z0-9]/ui', '_', $title);
	$ruTitle = trim($ruTitle, '_');
	$ruTitle = mb_strtolower($ruTitle);
	
	$ruLetters = array(	'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ы','ш','щ','ь','ъ','э','ю','я');
	$enLetters = array(	'a','b','v','g','d','e','e','zh','z','i','i','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','y','sh','sh','','','e','yu','ya');
	
	$newTitle = str_replace($ruLetters, $enLetters, $ruTitle);
    $newTitle = preg_replace('/_{2,}/ui', '_', $newTitle);
	return $newTitle;
}

function clearModifyUrl($url)
{
	return preg_replace('/[^А-Яа-яA-Za-z0-9\-_]/ui', '', $url);	
}


function returnRuMonth($month)
{
	$months = array('Нулябрь', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь','Октябрь','Ноябрь','Декабрь');

	$id = (int)$month;

	if(isset($months[$id]))
		return $months[$id];
	else
		return null; 
}

function getRusDate($date)
{
	$months_rus = array('Нулябрь', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября','октября','ноября','декабря');
	$str = date("j", $date) . ' ' . $months_rus[date("n", $date)] . ' ' . date("Y", $date);
	return $str;
}

//определяем ip пользователя
function getip() 
{
	//Получаем ip по умолчанию
	$direct_ip = '';
    if (getenv ('REMOTE_ADDR')) {
    	$direct_ip = getenv ('REMOTE_ADDR');
    }
	
	//Получаем ip proxy
	$proxy_ip = '';
	if (getenv ('HTTP_FORWARDED_FOR')) {$proxy_ip = getenv ('HTTP_FORWARDED_FOR');} 
    elseif (getenv ('HTTP_X_FORWARDED_FOR')) {$proxy_ip = getenv ('HTTP_X_FORWARDED_FOR');} 
    elseif (getenv ('HTTP_X_COMING_FROM')) {$proxy_ip = getenv ('HTTP_X_COMING_FROM');} 
    elseif (getenv ('HTTP_VIA')) {$proxy_ip = getenv ('HTTP_VIA');} 
    elseif (getenv ('HTTP_XROXY_CONNECTION')) {$proxy_ip = getenv ('HTTP_XROXY_CONNECTION');} 
    elseif (getenv ('HTTP_CLIENT_IP')) {$proxy_ip = getenv ('HTTP_CLIENT_IP');}

    if (empty($proxy_ip)) {
    	return $direct_ip;
    } 
	else {
		$is_ip = ereg('^([0-9]{1,3}\.){3,3}[0-9]{1,3}', $proxy_ip, $regs);
        if ($is_ip && (count($regs) > 0))
        	return $regs[0];
        else
        	return FALSE;
    }
	
}

function ip_template($ip, $ip_orig)
{
	$str = '<small>';
	
	if ($ip == $ip_orig) {
		if ($ip == '0.0.0.0')
			$str .= '&nbsp;';
		else {
			if (gethostbyaddr($ip) == $ip)
				$str .= $ip;
			else
				$str .= gethostbyaddr($ip) . ' [' . $ip. "]";
		}
	}
	else {
		if ($ip != '0.0.0.0')
			if (gethostbyaddr($ip) == $ip)
				$str .= $ip;
			else
				$str .= gethostbyaddr($ip) . ' [' . $ip. "]";
		if ($ip_orig != '0.0.0.0') {
			$str .= '<br/>--------<br/>';
			if (gethostbyaddr($ip_orig) == $ip_orig)
				$str .= $ip_orig;
			else
				$str .= gethostbyaddr($ip_orig) . " [" . $ip_orig . "]";
		}
	}
	$str .= '</small>';
	return $str;		
}

function packarray($array)
{
	return base64_encode(gzcompress(serialize($array))); 
}

function unpackarray($pack)
{
	return unserialize(gzuncompress(base64_decode($pack)));
}

function file_upload($subname = '/uploads/banners/', $params = array())
{
    $old_file=isset($params['old_file'])?get_post($params['old_file'], ''):get_post('old_file', '');
    $p = $old_file;
    $user_file=isset($params['user_file'])?$params['user_file']:'user_file';
    if( isset($_FILES[$user_file]['tmp_name']) && $_FILES[$user_file]['tmp_name'] != '' )
    {
        if($old_file)
        {
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$subname.$old_file))
            {
                unlink($_SERVER['DOCUMENT_ROOT'].$subname.$old_file);
            }
        }
        ereg("^.+\.(.+)", strtolower($_FILES[$user_file]['name']), $arr);        
        $p = $arr[0];
        $path = $subname.$p;
        move_uploaded_file($_FILES[$user_file]['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/'.$path);
        chmod ( $_SERVER['DOCUMENT_ROOT'].'/'.$path, 0777 );                                  
    }

    return $p;
}

/* безопасная загрузка файла, с шифрованием имени
--------------------------------------------------*/
function fileSafeUpload ($subname='uploads/banners/',$params=array())
{
    $old_file=isset($params['old_file'])?get_post($params['old_file'], ''):get_post('old_file', '');
    $p = $old_file;
    $user_file=isset($params['user_file'])?$params['user_file']:'user_file';
    if( isset($_FILES[$user_file]['tmp_name']) && $_FILES[$user_file]['tmp_name'] != '' )
    {
        if($old_file)
        {
            if(file_exists(SITE_DIR.$subname.$old_file))
            {
                unlink(SITE_DIR.$subname.$old_file);
            }
        }
        ereg("^.+\.(.+)", strtolower($_FILES[$user_file]['name']), $arr);
        $p = $arr[0];
        $path = $subname.$p;
	
	/* safe name
	--------------------------*/
	$ext = substr($p, (strlen($p)-3), 3);
	$name = substr($p, 0, (strlen($p)-4));
	
	$new_name = substr(md5(mktime()), 0, 10).'.'.$ext;
	$path = SITE_DIR.$subname.$new_name;

        move_uploaded_file($_FILES[$user_file]['tmp_name'], $path);
        chmod ( $path, 0777 );
    }
    return $new_name;
}

function remote_file_upload ($subname='uploads/banners/',$params=array(),$filename = 'user_file', $oldfilename = 'old_file', $ftp_data)
{
	/**
	 * @var $ftp_data = array();
	 * @param 
	 * 		['ftp_ip'] - server ip
	 * 		['ftp_login'] - ftp login
	 * 		['ftp_password'] - ftp password
	 * 		['ftp_site_path'] - path to site after ftp default folder
	 *
	 * @var $_FILES[$filename]['tmp_name'] = resource (uploaded file)
	 */

	if(isset($_FILES[$filename]['tmp_name']) && @is_uploaded_file($_FILES[$filename]['tmp_name']))
	{	
	   $ftp_conn = ftp_connect($ftp_data['ftp_ip']) or exit('Unable to sending data to ftp-server'); 
	   $uploaded = substr(md5(mktime()), 0,10).'.swf'; //file name
	   
		if (@ftp_login($ftp_conn, $ftp_data['ftp_login'], $ftp_data['ftp_password'])) 
		{
			ftp_pasv($ftp_conn, true); //enable passive mode
			ftp_chdir($ftp_conn, $ftp_data['ftp_site_path'].'/uploads/banners'); //change directory
			
			//bool ftp_fput ( resource ftp_stream, string remote_file, resource handle, int mode [, int startpos] )
			$fp = fopen($_FILES[$filename]['tmp_name'], 'r');
			ftp_fput($ftp_conn, $uploaded, $fp, FTP_BINARY);
			ftp_close($ftp_conn);
		}
		return($uploaded);
	}
	else 
    		return $_POST[$oldfilename];
}

/**
* Сборщик мусора
*/
function garbage_collection($display_profiler = true)
{
	global $app;

	if( !empty($app['profiler']) )
	{
		if( $display_profiler && !$app['request']->is_ajax && !defined('IN_SQL_ERROR') )
		{
			if( $app['config']['profiler.enabled'] && ($_SERVER['REMOTE_ADDR'] == '79.175.20.190' || false !== strpos($_SERVER['SERVER_NAME'], '.korden.net')) )
			{
				$app['profiler']->display();
			}
		}

		if( $app['config']['profiler.send_stats'] )
		{
			$app['profiler']->send_stats($app['config']['profiler.remote_host'], $app['config']['profiler.remote_port']);
		}
	}
	
	if( !empty($app['cache']) )
	{
		$app['cache']->unload();
	}

	if( !empty($app['db']) )
	{
		$app['db']->close();
	}
}

/**
* Создание ссылки на определенную страницу
*
* @return	string	Ссылка на страницу
*/
function generate_page_link($page, $base_url, $query_string)
{
	if( !$page )
	{
		return false;
	}

	if( $page == 1 )
	{
		return $base_url . $query_string;
	}

	$url_delim = ( !$query_string ) ? '?' : '&amp;';

	return $base_url . sprintf('%s%sp=%d', $query_string, $url_delim, $page);
}

/**
* Загрузка блока
*/
function get_page_block($page_url, $parent_id, $table)
{
	global $app;
	
	$sql = '
		SELECT
			id AS page_id,
			1 AS site_id,
			id_row AS parent_id,
			0 AS is_dir,
			activation AS page_enabled,
			0 AS page_display,
			title AS page_name,
			seo_title AS page_title,
			modifyurl AS page_url,
			"html" AS page_formats,
			"" AS page_redirect,
			preview AS page_preview,
			text AS page_text,
			"" AS page_handler,
			"" AS handler_method,
			seo_desc AS page_description,
			seo_keys AS page_keywords,
			0 AS page_noindex,
			"" AS page_image,
			0 AS page_type,
			viewed_text AS page_view_more,
			image AS page_poster,
			1 AS is_block
		FROM
			' . SQL_PREFIX . $table . '_gallery
		WHERE
			id_row = ' . $app['db']->check_value($parent_id) . '
		AND
			modifyurl = ' . $app['db']->check_value($page_url) . '
		AND
			activation = 1';
	$app['db']->query($sql);
	$row = $app['db']->fetchrow();
	$app['db']->freeresult();
	
	return $row;
}

/**
* Возвращает требуемое регулярное выражение
*
* @param	string	$type	Тип регулярного выражения
*
* @return	string			Код регулярного выражения
*/
function get_preg_expression($type)
{
	switch($type)
	{
		case 'url_symbols': return '[a-z\d\_\-\.\x{7f}-\x{ff}\(\)]+';
	}

	return false;
}

function get_server_name()
{
	global $app;
	
	$hostname = mb_strtolower($app['request']->header('Host') ?: $app['request']->server('SERVER_NAME'));
	$hostname = 0 === strpos($hostname, 'www.') ? substr($hostname, 4) : $hostname;
	$hostname = (false !== $pos = strpos($hostname, ':')) ? substr($hostname, 0, $pos) : $hostname;
		
	return $hostname;
}

/**
* Поиск URL сайта по его уникальному идентификатору
*/
function get_site_info_by_id($site_id)
{
	global $app;
	
	$sites = $app['cache']->obtain_sites();
	
	if( isset($sites[$site_id]) )
	{
		return array(
			'default'  => (int) $sites[$site_id]['site_default'],
			'domain'   => $sites[$site_id]['site_url'],
			'id'       => (int) $sites[$site_id]['site_id'],
			'language' => $sites[$site_id]['site_language'],
			'title'    => $sites[$site_id]['site_title']
		);
	}
	
	return false;
}

/**
* Поиск информации о сайте по его доменному имени
* и языку, если передан просматриваемой URL страницы
*
* Если страница не указана, то будет выдан сайт
* на языке по умолчанию (site_default = 1)
*/
function get_site_info_by_url($url, $page = '')
{
	global $app;

	$language = '';
	$page     = trim($page, '/');
	$params   = $page ? explode('/', $page) : array();
	
	if( !empty($params) && strlen($params[0]) == 2 )
	{
		$language = $params[0];
	}
	
	if( $language )
	{
		return get_site_info_by_url_lang($url, $language);
	}
	
	$hostnames = $app['cache']->obtain_hostnames();
	
	if( isset($hostnames[$url]) )
	{
		return get_site_info_by_id($hostnames[$url]);
	}
	
	return false;
}

/**
* Поиск URL сайта по его доменному имени и локализации
*/
function get_site_info_by_url_lang($url, $lang)
{
	global $app;
	
	$hostnames = $app['cache']->obtain_hostnames();
	
	if( isset($hostnames[$url . '_' . $lang]) )
	{
		return get_site_info_by_id($hostnames[$url . '_' . $lang]);
	}
	
	return false;
}

/**
* Размер в понятной человеку форме, округленный к ближайшему ГБ, МБ, КБ
*
* @param	int		$size		Размер
* @param	int		$rounder	Необходимое количество знаков после запятой
* @param	string	$min		Минимальный размер ('КБ', 'МБ' и т.п.)
* @param	string	$space		Разделитель между числами и текстом (1< >МБ)
*
* @return	string				Размер в понятной человеку форме
*/
function humn_size($size, $rounder = '', $min = '', $space = '&nbsp;')
{
	$sizes = array('байт', 'КБ', 'МБ', 'ГБ', 'ТБ', 'ПБ', 'ЭБ', 'ЗБ', 'ЙБ');
	static $rounders = array(0, 0, 1, 2, 3, 3, 3, 3, 3);

	$size = (float) $size;
	$ext  = $sizes[0];
	$rnd  = $rounders[0];

	if( $min == 'КБ' && $size < 1024 )
	{
		$size    = $size / 1024;
		$ext     = 'КБ';
		$rounder = 1;
	}
	else
	{
		for( $i = 1, $cnt = sizeof($sizes); ($i < $cnt && $size >= 1024); $i++ )
		{
			$size = $size / 1024;
			$ext  = $sizes[$i];
			$rnd  = $rounders[$i];
		}
	}

	if( !$rounder )
	{
		$rounder = $rnd;
	}

	return round($size, $rounder) . $space . $ext;
}

/**
* Внутренняя ссылка
*
* @param	string	$url		ЧПУ ссылка
* @param	string	$prefix		Префикс (по умолчанию $app['config']['site_root_path'])
*
* @return	string				Готовый URL
*/
function ilink($url = '', $prefix = false)
{
	global $app;

	/**
	* Этапы обработки URL: а) сайт, находящийся в дочерней папке; б) на другом домене; в) в корне;
	*
	* 1а) /csstats		1б) http://wc3.ivacuum.ru/	1в) /
	* 2а) /csstats/		2б) http://wc3.ivacuum.ru/	2в) /en/
	*/
	if( 0 === strpos($url, '/') )
	{
		/**
		* Ссылка от корня сайта
		*
		* /acp/
		* /about.html
		*/
		$link = $app['config']['site_root_path'];
		$url  = substr($url, 1);
	}
	elseif( 0 === strpos($url, 'http://') )
	{
		$link = 'http://';
		$url  = substr($url, 7);
	}
	elseif( 0 === strpos($url, '//') )
	{
		$link = '//';
		$url  = substr($url, 2);
	}
	else
	{
		$link = ( $prefix === false ) ? $app['config']['site_root_path'] : $prefix;
		$link .= ( substr($link, -1) == '/' ) ? '' : '/';
	}

	/**
	* Добавляем язык, если выбран отличный от языка по умолчанию и ссылка от корня сайта
	*
	* Если язык уже присутствует в ссылке, то пропускаем этот шаг
	*/
	if( ($link == $app['config']['site_root_path'] && $prefix === false) || (false !== strpos($prefix, 'ivacuum.ru')) )
	{
		if( !$app['site_info']['default'] && (false === strpos($link . $url, sprintf('/%s/', $app['site_info']['language']))) )
		{
			$link = sprintf('%s%s/', $link, $app['site_info']['language']);
		}
	}
	
	$link .= $url;
	$ary = pathinfo($url);
	
	if( isset($ary['extension']) || substr($link, -1) == '/' || !$app['config']['router.default_extension'] )
	{
		return $link;
	}
	
	return sprintf('%s/', $link);
}

function install_site()
{
	global $app;
	
	$sql_ary = array(
		'site_id'       => 1,
		'site_language' => 'ru',
		'site_locale'   => 'ru_RU.utf8',
		'site_title'    => '',
		'site_url'      => get_server_name(),
		'site_aliases'  => '',
		'site_default'  => 1
	);
			
	$sql = 'INSERT INTO ' . SITES_TABLE . ' ' . $app['db']->build_array('INSERT', $sql_ary);
	$app['db']->query($sql);
}

/**
* Загрузка констант
*/
function load_constants()
{
	global $app;
	
	if( !function_exists('apc_fetch') )
	{
		return false;
	}

	return apc_load_constants($app['acm.prefix'] . '_constants');
}

/**
* Навигационная ссылка
*
* @param	string	$url	Ссылка на страницу
* @param	string	$text	Название страницы
* @param	string	$image	Изображение
*/
function navigation_link($url, $text, $image = false)
{
	global $app;
	
	$app['template']->append('nav_links', array(
		'IMAGE' => $image,
		'TEXT'  => $text,
		'URL'   => $url
	));
}

/**
* Возвращает число в заданном формате
*
* В данный момент для всех языков оформление едино:
* 12345678 -> 12 345 678
*
* @param	int	$value	Число
*
* @return	int			Число в заданном формате
*/
function num_format($value, $decimals = 0)
{
	global $app;
	
	return number_format($value, $decimals, $app['config']['number_dec_point'], $app['config']['number_thousands_sep']);
}

/**
* Возвращает число в пределах $min:$max
*
* @param	int	$value	Число
* @param	int	$min	Минимальная граница
* @param	int $max	Максимальная граница
*
* @return	int			Число не менее $min и не более $max
*/
function num_in_range($value, $min, $max = false)
{
	$max = ( $max ) ?: $value;

	return ( $value < $min ) ? $min : (($value > $max) ? $max : $value);
}

/**
* Создание случайной строки заданной длины
*
* @param	int		$length		Длина строки
*
* @return	string				Случайная строка заданной длины
*/
function make_random_string($length = 10)
{
	return substr(str_shuffle(preg_replace('#[^0-9a-zA-Z]#', '', crypt(uniqid(mt_rand(), true)))), 0, $length);
}

/**
* Переход по страницам
*
* Проверяем наличие выбранной страницы. Устанавливаем данные шаблона.
*
* @param	int		$on_page	Количество элементов на странице
* @param	int		$overall	Общее количество элементов
* @param	string	$link		Базовый адрес (для ссылок перехода по страницам)
* @param	string	$page_var	Переменная в адресе, содержащая номер текущей страницы
*/
function pagination($on_page, $overall, $link, $page_var = 'p')
{
	global $app;

	$base_url     = $link;
	$p            = $app['request']->variable($page_var, 1);
	$query_string = '';
	$sort_count   = $app['request']->variable('sc', $on_page);
	$sort_dir     = $app['request']->variable('sd', 'd');
	$sort_key     = $app['request']->variable('sk', 'a');
	$start        = ($p * $sort_count) - $sort_count;

	/**
	* Нужно ли ссылки на страницы указывать с параметрами
	*/
	if( $sort_count != $on_page || $sort_dir != 'd' || $sort_key != 'a' )
	{
		if( $sort_count != $on_page )
		{
			$link .= ((false !== strpos($link, '?')) ? '&' : '?') . 'sc=' . $sort_count;
		}

		if( $sort_dir != 'd' )
		{
			$link .= ((false !== strpos($link, '?')) ? '&' : '?') . 'sd=' . $sort_dir;
		}

		if( $sort_key != 'a' )
		{
			$link .= ((false !== strpos($link, '?')) ? '&' : '?') . 'sk=' . $sort_key;
		}
	}

	/* Общее количество страниц */
	$pages = max(1, intval(($overall - 1) / $sort_count) + 1);

	/* Проверка номера страницы */
	if( !$p || $p > $pages || $p <= 0 )
	{
		trigger_error('PAGE_NOT_FOUND');
	}

	if( false !== $q_pos = strpos($base_url, '?') )
	{
		/**
		* Если в адресе присутствует query_string:
		* /news/5/?sid=1
		*
		* то разбиваем его на две части:
		* /news/5/
		* ?sid=1
		*/
		$query_string = substr($base_url, $q_pos);
		$base_url     = substr($base_url, 0, $q_pos);
	}

	$url_delim = !$query_string ? '?' : '&amp;';
	$url_next = $url_prev = 0;

	if( $pages > $p )
	{
		if( $p > 1 )
		{
			$url_prev = $p - 1;
		}

		$url_next = $p + 1;
	}
	elseif( $pages == $p && $pages > 1 )
	{
		$url_prev = $p - 1;
	}
	
	$app['template']->assign(array(
		'pagination' => array(
			'ITEMS'   => $overall,
			'NEXT'    => generate_page_link($url_next, $base_url, $query_string),
			'ON_PAGE' => $sort_count,
			'PAGE'    => $p,
			'PAGES'   => $pages,
			'PREV'    => generate_page_link($url_prev, $base_url, $query_string),
			'VAR'     => $page_var,
			'URL'     => $link
		)
	));

	return array(
		'offset'  => (int) $start,
		'on_page' => (int) $sort_count,
		'p'       => (int) $p,
		'pages'   => (int) $pages
	);
}

/**
* Формы слова во множественном числе
*
* @param	int		$n		Число
* @param	array	$forms	Формы слова
*
* @param	string			Фраза во множественном числе
*/
function plural($n = 0, $forms, $format = '%s %s')
{
	if( !$forms )
	{
		return;
	}

	$forms = explode(';', $forms);

	switch( 'ru' )
	{
		/**
		* Русский язык
		*/
		case 'ru':

			if( sizeof($forms) < 3 )
			{
				$forms[2] = $forms[1];
			}

			$plural = ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);

		break;
		/**
		* Язык по умолчанию - английский
		*/
		default:

			$plural = ($n == 1) ? 0 : 1;

		break;
	}
	
	return sprintf($format, num_format($n), $forms[$plural]);
}

/**
* Переадресация
*
* @param	string	$url	Адрес для мгновенного перенаправления
*/
function redirect($url, $status_code = 302)
{
	if( false !== strpos(urldecode($url), "\n") || false !== strpos(urldecode($url), "\r") )
	{
		trigger_error('Bad URL.', E_USER_ERROR);
	}
	
	if( $status_code != 302 )
	{
		send_status_line($status_code);
	}
	
	header('Location: ' . $url);
	garbage_collection();
	exit;
}

/**
* Установка констант
*/
function set_constants($constants)
{
	global $app;

	if( !function_exists('apc_fetch') )
	{
		foreach( $constants as $key => $value )
		{
			define($key, $value);
		}
		
		return;
	}
	
	if( !$app['acm.prefix'] )
	{
		return;
	}
	
	apc_define_constants($app['acm.prefix'] . '_constants', $constants);
}

/**
* Вывод заголовка
*
* send_status_line(404, 'Not Found');
*
* HTTP/1.x 404 Not Found
*/
function send_status_line($code, $message = '')
{
	global $app;
	
	if( !$message )
	{
		switch( $code )
		{
			case 200: $message = 'OK'; break;
			case 201: $message = 'Created'; break;
			case 202: $message = 'Accepted'; break;
			case 204: $message = 'No Content'; break;
			
			case 301: $message = 'Moved Permanently'; break;
			case 302: $message = 'Found'; break;
			case 303: $message = 'See Other'; break;
			case 304: $message = 'Not Modified'; break;
			
			case 400: $message = 'Bad Request'; break;
			case 401: $message = 'Unauthorized'; break;
			case 403: $message = 'Forbidden'; break;
			case 404: $message = 'Not Found'; break;
			case 405: $message = 'Method Not Allowed'; break;
			case 409: $message = 'Conflict'; break;
			case 410: $message = 'Gone'; break;
			
			case 500: $message = 'Internal Server Error'; break;
			case 501: $message = 'Not Implemented'; break;
			case 502: $message = 'Bad Gateway'; break;
			case 503: $message = 'Service Unavailable'; break;
			case 504: $message = 'Gateway Timeout'; break;
			
			default: return;
		}
	}
	
	if( substr(strtolower(PHP_SAPI), 0, 3) === 'cgi' )
	{
		header(sprintf('Status: %d %s', $code, $message), true, $code);
		return;
	}
	
	if( false != $version = $app['request']->server('SERVER_PROTOCOL') )
	{
		header(sprintf('%s %d %s', $version, $code, $message), true, $code);
		return;
	}
	
	header(sprintf('HTTP/1.0 %d %s', $code, $message), true, $code);
}

/**
* Создание ЧПУ ссылки с использованием символов выбранного языка сайта
*
* @param	string	$url	Входная ссылка
*
* @return	string	$result	ЧПУ ссылка
*/
function seo_url($url, $lang = 'ru')
{
	$url = htmlspecialchars_decode($url);
	
	switch( $lang )
	{
		case 'ru': $pattern = '/[^а-яa-z\d\.]/u'; break;
		default:

			$pattern = '/[^a-z\d\.]/u'; break;

		break;
	}

	/* Отсекаем неподходящие символы */
	$result = trim(preg_replace($pattern, '_', mb_strtolower(htmlspecialchars_decode($url))), '_');

	/**
	* Укорачиваем однообразные последовательности символов
	* _. заменяем на _
	* Убираем точку в конце
	*/
	$result = preg_replace(array('/\.{2,}/', '/_\./', '/_{2,}/', '/(.*)\./'), array('', '_', '_', '$1'), $result);

	return $result;
}
