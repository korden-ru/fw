<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Поле формы
*/
class generic implements \ArrayAccess, \IteratorAggregate, \Countable
{
	public $data = array();
	
	function __construct($data)
	{
		$data['is_valid'] = true;
		$data['value'] = '';
		
		$this->data = $data;
	}
	
	public function is_valid()
	{
		$this->data['is_valid'] = $this->validate();
		
		return $this->data['is_valid'];
	}
	
	public function validate()
	{
		return true;
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
