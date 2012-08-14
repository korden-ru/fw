<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Флажок
*/
class checkbox extends generic
{
	public function validate()
	{
		if( $this->data['field_disabled'] )
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
