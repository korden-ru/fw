<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Число (с плавающей запятой)
*/
class number extends generic
{
	public function get_default_value($is_bound = false)
	{
		return (float) sprintf('%.2f', $this->data['field_value']);
	}
	
	public function set_value($value)
	{
		$this->data['value'] = (float) $value;
	}
	
	public function validate()
	{
		if( $this->data['field_disabled'] || $this->data['field_readonly'] )
		{
			return true;
		}
		
		if( $this->data['field_required'] && !$this->data['value'] )
		{
			return false;
		}
		
		return true;
	}
}
