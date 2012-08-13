<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Электронный адрес
*/
class email extends generic
{
	public function validate()
	{
		if( $this->data['field_required'] && !$this->data['value'] )
		{
			$this->data['is_valid'] = false;
		}
		
		return $this->data['is_valid'];
	}
}
