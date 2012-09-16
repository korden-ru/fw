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
	
	protected $config;
	
	function __construct($data, $config)
	{
		$data['is_valid'] = true;
		$data['value'] = $data['field_value'];
		
		$this->config = $config;
		$this->data = $data;
	}
	
	public function is_valid()
	{
		return $this->data['is_valid'] = $this->validate();
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
