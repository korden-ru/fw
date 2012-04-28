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
		$this->ctime = time();
		$this->data  = $row;
	}
	
	public function _set_cache($cache)
	{
		$this->cache = $cache;
		
		return $this;
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
	}
	
	/**
	* Лог операций
	*/
	protected function log($text)
	{
		printf("%s: [%s] %s\n", date('Y-m-d H:i:s'), $this->data['cron_script'], $text);
	}
}
