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
	public function validate()
	{
		if( $this->data['field_required'] && !$this->data['field_value'] )
		{
			$this->is_valid = false;
		}
		
		return $this->is_valid;
	}
}
