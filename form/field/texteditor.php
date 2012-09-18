<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Текстовое поле
*/
class textarea extends generic
{
	public function set_value($value)
	{
		/* Визуальному редактору разрешено присылать html-код */
		$this->data['value'] = htmlspecialchars_decode($value, ENT_COMPAT);
	}
	
	public function validate()
	{
		if( $this->data['field_required'] && !$this->data['value'] )
		{
			return false;
		}
		
		return true;
	}
}
