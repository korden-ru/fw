<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* URL
*/
class url extends generic
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