<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\form\field;

/**
* Число (с плавающей запятой)
*/
class number extends generic
{
	public function get_default_value($is_bound = false)
	{
		/**
		* Строковое значение, чтобы php не преобразовывал тип
		* и передал исходное значение, которое ввел пользователь
		*/
		return (string) $this->data['field_value'];
	}
	
	public function set_value($value)
	{
		$format = '%.' . $this->data['field_precision'] . 'F';
		$locale = localeconv();
		
		/* Обработка разделителей с учетом текущей локали */
		$value = str_replace(array($locale['thousands_sep'], $locale['decimal_point']), array('', '.'), $value);
		
		switch( $this->data['field_rounding_mode'] )
		{
			/**
			* Отсечение лишней части
			*
			* 9.111 => 9.11
			* 9.555 => 9.55
			* 9.999 => 9.99
			*/
			case 0:
			
				$value = sprintf($format, floor($value * pow(10, $this->data['field_precision'])) / pow(10, $this->data['field_precision']));
			
			break;
			/**
			* Округление при 0-4 в меньшую сторону, при 5-9 - в большую
			*
			* 9.111 => 9.11
			* 9.555 => 9.56
			* 9.999 => 10
			*/
			case 1:
			
				$value = round($value, $this->data['field_precision'], PHP_ROUND_HALF_UP);
				
			break;
			/**
			* Округление при 0-5 в меньшую сторону, при 6-9 - в большую
			*
			* 9.111 => 9.11
			* 9.555 => 9.55
			* 9.999 => 10
			*/
			case 2:
			
				$value = round($value, $this->data['field_precision'], PHP_ROUND_HALF_DOWN);
				
			break;
			/**
			* Округление всегда в большую сторону
			*
			* 9.111 => 9.12
			* 9.555 => 9.56
			* 9.999 => 10
			*/
			case 3:
			
				$value = sprintf($format, ceil($value * pow(10, $this->data['field_precision'])) / pow(10, $this->data['field_precision']));
				
			break;
		}
		
		$this->data['value'] = (float) $value;
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
		
		return true;
	}
}
