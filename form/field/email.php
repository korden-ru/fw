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
			return false;
		}
		
		if( !preg_match(sprintf('#%s#', get_preg_expression('email')), $this->data['value'], $matches) )
		{
			return false;
		}
		
		return true;
	}
}
