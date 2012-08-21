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
			return false;
		}
		
		/**
		* Проверка длины
		* Видоизмененные данные перестанут быть корректными, поэтому сбрасываем ввод
		*/
		if( $this->data['value'] )
		{
			$len = mb_strlen($this->data['value']);
			
			if( $len < $this->config['form.url.min_chars'] || $len > $this->config['form.url.max_chars'] )
			{
				$this->data['value'] = '';

				return !$this->data['field_required'];
			}
		}
		
		if( $this->data['field_default_protocol'] )
		{
			if( !preg_match('#^(https?|ftp)://#', $this->data['value']) )
			{
				$this->data['value'] = $this->data['field_default_protocol'] . $this->data['value'];
			}
		}
		
		if( $this->data['field_pattern'] && !preg_match(sprintf('#%s#', $this->data['field_pattern']), $this->data['value']) )
		{
			return false;
		}

		if( false === $ary = parse_url($this->data['value']) )
		{
			$this->data['value'] = '';
			
			return !$this->data['field_required'];
		}
		
		/* Сбор нового URL без имени и пароля */
		$this->data['value'] = $ary['scheme'] . '://' . $ary['host'] . (isset($ary['port']) ? ':' . $ary['port'] : '') . (isset($ary['path']) ? $ary['path'] : '/') . (isset($ary['query']) ? '?' . $ary['query'] : '') . (isset($ary['fragment']) ? '#' . $ary['fragment'] : '');

		return true;
	}
}
