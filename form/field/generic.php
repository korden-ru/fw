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
		$this->config = $config;
		// $this->fill_default_data($data);
		$this->data = $this->fill_default_data($data);
		
		$this->data['is_valid'] = true;
		$this->data['value'] = $this->get_default_value();
	}
	
	public function get_default_value($is_bound = false)
	{
		return (string) $this->data['field_value'];
	}
	
	public function is_valid()
	{
		return $this->data['is_valid'] = $this->validate();
	}
	
	public function set_value($value)
	{
		$this->data['value'] = $value;
	}
	
	public function validate()
	{
		return true;
	}
	
	protected function fill_default_data($data)
	{
		// $this->data = array(
		// 	'field_type'             => @$row['field_type'] ?: @$row['type'] ?: '',
		// 	'field_title'            => @$row['field_title'] ?: @$row['title'] ?: '',
		// 	'field_alias'            => @$row['field_alias'] ?: @$row['alias'] ?: @$row['name'] ?: '',
		// 	'field_required'         => @$row['field_required'] ?: @$row['required'] ?: 0,
		// 	'field_disabled'         => @$row['field_disabled'] ?: @$row['disabled'] ?: 0,
		// 	'field_readonly'         => @$row['field_readonly'] ?: @$row['readonly'] ?: 0,
		// 	'field_multiple'         => @$row['field_multiple'] ?: @$row['multiple'] ?: 0,
		// 	'field_rounding_mode'    => @$row['field_rounding_mode'] ?: @$row['rounding_mode'] ?: 0,
		// 	'field_precision'        => @$row['field_precision'] ?: @$row['precision'] ?: 0,
		// 	'field_always_empty'     => @$row['field_always_empty'] ?: @$row['always_empty'] ?: 0,
		// 	'field_default_protocol' => @$row['field_default_protocol'] ?: @$row['default_protocol'] ?: '',
		// 	'field_width'            => @$row['field_width'] ?: @$row['width'] ?: '',
		// 	'field_height'           => @$row['field_height'] ?: @$row['height'] ?: '',
		// 	'field_autofocus'        => @$row['field_autofocus'] ?: @$row['autofocus'] ?: 0,
		// 	'field_tabindex'         => @$row['field_tabindex'] ?: @$row['tabindex'] ?: 0,
		// 	'field_min'              => @$row['field_min'] ?: @$row['min'] ?: '',
		// 	'field_max'              => @$row['field_max'] ?: @$row['max'] ?: '',
		// 	'field_pattern'          => @$row['field_pattern'] ?: @$row['pattern'] ?: '',
		// 	'field_value'            => @$row['field_value'] ?: @$row['value'] ?: '',
		// 	'field_values'           => @$row['field_values'] ?: @$row['values'] ?: '',
		// 	'field_placeholder'      => @$row['field_placeholder'] ?: @$row['placeholder'] ?: '',
		// 	'field_prepend'          => @$row['field_prepend'] ?: @$row['prepend'] ?: '',
		// 	'field_append'           => @$row['field_append'] ?: @$row['append'] ?: '',
		// 	'field_help_inline'      => @$row['field_help_inline'] ?: @$row['help_inline'] ?: '',
		// 	'field_help'             => @$row['field_help'] ?: @$row['help'] ?: '',
		// 	'field_repeated'         => @$row['field_repeated'] ?: @$row['repeated'] ?: '',
		// 	'field_invalid_message'  => @$row['field_invalid_message'] ?: @$row['invalid_message'] ?: '',
		// 	'field_attr'             => @$row['field_attr'] ?: @$row['attr'] ?: '',
		// 	
		// 	'is_valid' => true,
		// );
		
		// $this->data['value'] = $this->get_default_value();
		
		return $data;
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
