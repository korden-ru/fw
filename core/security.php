<?php

namespace engine\core;

class security
{
	function __construct()
	{
		$int = array('id', 'nid', 'sid', 'aid', 'uid', 'mid', 'pid', 'tab', 'menu', 'p', 'page');
		$words = array('login', 'password', 'url', 'page');

		foreach($_GET as $key => $value)
		{
			if(in_array($value, $int))
				$_GET[$key] = $this->clearFor('numbers', $value);
	
			if(in_array($value, $words))
				$_GET[$key] = $this->clearFor('words', $value);
		}

		foreach($_POST as $key => $value)
		{
			if(in_array($value, $int))
				$_POST[$key] = $this->clearFor('numbers', $value);
	
			if(in_array($value, $words))
				$_POST[$key] = $this->clearFor('default', $value);
		}
	}

	public function ClearFor($type, $data, $additional_symbols=null)
	{
		if($type == 'numbers')
			$diap = '0-9';
		elseif($type == 'words')
			$diap = 'a-zA-Zа-яА-я';
		else
			$diap = '0-9a-zA-Zа-яА-я';
			
		$data = preg_replace('/[^'.$diap.$additional_symbols.']/ui', ' ', $data);
		
		return $data;
	}
}
