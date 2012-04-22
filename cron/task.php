<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cron;

/**
* Задача по расписанию
*/
class task
{
	public $data = array();

	protected $cache;
	protected $config;
	protected $ctime;
	protected $db;

	function __construct(array $row)
	{
		global $cache, $config, $db;

		$this->cache  =& $cache;
		$this->config =& $config;
		$this->ctime  = time();
		$this->data   = $row;
		$this->db     =& $db;
	}
	
	/**
	* Лог операций
	*/
	protected function log($text)
	{
		printf("%s: [%s] %s\n", date('Y-m-d H:i:s'), $this->data['cron_script'], $text);
	}
}
