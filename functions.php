<?php
/**
* @package korden.fw
* @copyright (c) 2013
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
			site_' . $table . '_gallery
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

function install_site()
{
	global $app;
	
	$sql_ary = array(
		'site_id'       => 1,
		'site_language' => 'ru',
		'site_locale'   => 'ru_RU.UTF-8',
		'site_title'    => '',
		'site_url'      => $app['request']->server_name,
		'site_aliases'  => '',
		'site_default'  => 1,
	);
			
	$sql = 'INSERT INTO site_sites ' . $app['db']->build_array('INSERT', $sql_ary);
	$app['db']->query($sql);
}
