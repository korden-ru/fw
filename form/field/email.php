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
		if( $this->data['field_disabled'] || $this->data['field_readonly'] )
		{
			return true;
		}
		
		if( $this->data['field_required'] && !$this->data['value'] )
		{
			return false;
		}
		
		if( $this->data['field_pattern'] && !preg_match(sprintf('#%s#', $this->data['field_pattern']), $this->data['value']) )
		{
			return false;
		}
		elseif( !$this->data['field_pattern'] && !preg_match(sprintf('#%s#', get_preg_expression('email')), $this->data['value']) )
		{
			return false;
		}
		
		return true;
	}
}
