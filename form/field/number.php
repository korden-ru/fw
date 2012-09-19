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
		return (string) $this->data['field_value'];
	}
	
	public function set_value($value)
	{
		$locale = localeconv();
		
		$this->data['value'] = (float) str_replace(array($locale['thousands_sep'], $locale['decimal_point']), array('', '.'), $value);
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
