<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Текстовое поле
*/
class text extends generic
{
	/**
	* required
	* disabled
	* readonly
	* trim
	* rounding_mode
	* precision
	* always_empty
	* default_protocol
	*/
	public function validate()
	{
		if( $this->data['field_disabled'] || $this->data['field_readonly'] )
		{
			return true;
		}
		
		if( $this->data['field_required'] && !$this->data['value'] )
		{
			$this->data['is_valid'] = false;
		}
		
		return $this->data['is_valid'];
	}
}
