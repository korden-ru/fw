<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\captcha;

/**
* Проверка кода подтверждения
*/
class validator
{
	private $code;
	private $confirm_code;
	private $solved = false;
	
	protected $config;
	protected $request;
	protected $user;

	function __construct($config, $request, $user)
	{
		$this->config  = $config;
		$this->request = $request;
		$this->user    = $user;
		
		$this->confirm_code = mb_strtoupper($this->request->variable('confirm_code', ''));
	}

	/**
	* Верно ли введен код подтверждения
	*/
	public function is_solved()
	{
		if( !$this->config['confirm.enable'] )
		{
			return true;
		}
		
		if( $this->request->is_set('confirm_code') && $this->solved === false )
		{
			$this->validate();
		}
		
		return $this->solved;
	}
	
	/**
	* Сброс кода подтверждения
	*/
	public function reset()
	{
		if( !$this->config['confirm.enable'] )
		{
			return;
		}
		
		if( $this->solved )
		{
			unset($_SESSION['confirm_code']);
		}
	}
	
	/**
	* Сравнение кода с эталонным
	*/
	private function check_code()
	{
		return (strcasecmp($this->code, $this->confirm_code) === 0);
	}
	
	/**
	* Загрузка кода
	*/
	private function load_code()
	{
		if( !isset($_SESSION['confirm_code']) || !$_SESSION['confirm_code'] )
		{
			return false;
		}
		
		$this->code = $_SESSION['confirm_code'];
	}
	
	/**
	* Проверка ввода кода
	*/
	private function validate()
	{
		if( empty($this->code) )
		{
			$this->load_code();
		}
		
		$this->solved = $this->check_code();
	}
}
