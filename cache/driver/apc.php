<?php
/** 
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache\driver;

/**
* Класс кэша
*/
class apc extends memory
{
	protected $extension = 'apc';
	
	/**
	* Очистка кэша
	*/
	public function purge()
	{
		apc_clear_cache('user');
		
		parent::purge();
	}

	/**
	* Удаление записи из кэша
	*/
	public function _delete($var)
	{
		return apc_delete($this->prefix . $var);
	}
	
	/**
	* Чтение записи из кэша
	*/
	public function _get($var)
	{
		return apc_fetch($this->prefix . $var);
	}
	
	/**
	* Запись данных в кэш
	*/
	public function _set($var, $data, $ttl = 2592000)
	{
		return apc_store($this->prefix . $var, $data, $ttl);
	}
}
