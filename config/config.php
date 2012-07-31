<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\config;

/**
* Настройки сайта
*/
class config implements \ArrayAccess, \IteratorAggregate, \Countable
{
	protected $config;
	
	function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	* Удаление настройки
	*/
	public function delete($key)
	{
		unset($this->config[$key]);
	}
	
	/**
	* Увеличение значения настройки (счетчика)
	*/
	public function increment($key, $increment = 1)
	{
		if( !isset($this->config[$key]) )
		{
			$this->config[$key] = 0;
		}
		
		$this->config[$key] += $increment;
	}
	
	/**
	* Установка нового значения настройки
	*/
	public function set($key, $value)
	{
		$this->config[$key] = $value;
	}
	
	/**
	* Установка нового значения только если предыдущее совпадает или вовсе отсутствует
	*/
	public function set_atomic($key, $old_value, $new_value)
	{
		if( !isset($this->config[$key]) || $this->config[$key] == $old_value )
		{
			$this->config[$key] = $new_value;
			return true;
		}
		
		return false;
	}

	/**
	* Реализация интерфейса Countable
	*/
	public function count()
	{
		return sizeof($this->config);
	}
	
	/**
	* Реализация интерфейса IteratorAggregate
	*/
	public function getIterator()
	{
		return new ArrayIterator($this->config);
	}
	
	/**
	* Реализация интерфейса ArrayAccess
	*/
	public function offsetExists($key)
	{
		return isset($this->config[$key]);
	}
	
	public function offsetGet($key)
	{
		return isset($this->config[$key]) ? $this->config[$key] : '';
	}
	
	public function offsetSet($key, $value)
	{
		$this->config[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		trigger_error('Вместо функции unset() следует использовать метод config::delete()');
	}
}
