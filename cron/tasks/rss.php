<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cron\tasks;

use engine\cron\task;

/**
* Обработка RSS
*/
class rss extends task
{
	public function get_rss_xml_data($url, $timeout = false)
	{
		$timeout = ( $timeout !== false ) ? intval($timeout) : $this->config['cron_rss_timeout'];
		
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $timeout);
		$result = curl_exec($c);
		curl_close($c);
		
		return simplexml_load_string($result);
	}
}
