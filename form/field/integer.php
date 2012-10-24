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
	
	protected function fill_default_data($data)
	{
		return $data;
	}
}
