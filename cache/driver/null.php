<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache\driver;

class null
{
	function __construct()
	{
	}
	
	public function _get($var)
	{
		return false;
	}

	public function _set($var, $data, $ttl = 0)
	{
	}
	
	public function _set_db()
	{
	}
	
	public function clean($var, $table = '')
	{
	}

	public function delete($var, $table = '')
	{
	}
	
	public function get($var)
	{
		return false;
	}

	public function load()
	{
		return true;
	}

	public function purge()
	{
	}

	public function save()
	{
	}

	public function set($var, $data, $ttl = 0)
	{
	}
	
	public function set_prefix($prefix = '')
	{
	}

	public function sql_exists($query_id)
	{
		return false;
	}

	public function sql_fetchfield($query_id, $field)
	{
		return false;
	}

	public function sql_fetchrow($query_id)
	{
		return false;
	}

	public function sql_freeresult($query_id)
	{
		return false;
	}

	public function sql_load($query)
	{
		return false;
	}

	public function sql_rowseek($rownum, $query_id)
	{
		return false;
	}
	
	public function sql_save($query, &$query_result, $ttl)
	{
	}

	public function unload()
	{
	}

	private function _exists($var)
	{
		return false;
	}
}
