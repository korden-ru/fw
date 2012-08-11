<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Поле формы
*/
class abstract implements \ArrayAccess, \IteratorAggregate, \Countable
{
	protected $data = array();
	
	function __construct($data)
	{
		$this->data = $data;
	}
	
	public function validate()
	{
	}

	/**
	* Реализация интерфейса Countable
	*/
	public function count()
	{
		return sizeof($this->data);
	}
	
	/**
	* Реализация интерфейса IteratorAggregate
	*/
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
	
	/**
	* Реализация интерфейса ArrayAccess
	*/
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}
	
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		trigger_error('Функция unset() не поддерживается');
	}
}
