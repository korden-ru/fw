<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Число
*/
class integer extends number
{
	public function set_value($value)
	{
		parent::set_value($value);
		
		$this->data['value'] = (int) $this->data['value'];
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
		
		if( $this->data['field_min'] != '' && $this->data['value'] < $this->data['field_min'] )
		{
			return false;
		}
		
		if( $this->data['field_max'] != '' && $this->data['value'] > $this->data['field_max'] )
		{
			return false;
		}
		
		return true;
	}
	
	protected function fill_default_data($data)
	{
		return $data;
	}
}
