<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache\driver;

/**
* Memcache
*/
class memcache extends memory
{
	protected $extension = 'memcache';

	private $memcache;
	private $flags = 0;

	function __construct($prefix = '')
	{
		parent::__construct($prefix);

		$this->memcache = new \Memcache;
		$this->memcache->pconnect('unix:///var/run/memcached/memcached.lock', 0);
	}

	/**
	* Удаление записи
	*/
	public function _delete($var)
	{
		return $this->memcache->delete($var, 0);
	}

	/**
	* Извлечение записи
	*/
	public function _get($var)
	{
		return $this->memcache->get($var);
	}

	/**
	* Обновление/добавление записи
	*/
	public function _set($var, $data, $ttl = 2592000)
	{
		if( !$this->memcache->replace($var, $data, $this->flags, $ttl) )
		{
			return $this->memcache->set($var, $data, $this->flags, $ttl);
		}

		return true;
	}

	/**
	* Сброс данных
	*/
	public function purge()
	{
		$this->memcache->flush();

		parent::purge();
	}

	/**
	* Завершение подключения
	*/
	public function unload()
	{
		parent::unload();

		$this->memcache->close();
	}
}
