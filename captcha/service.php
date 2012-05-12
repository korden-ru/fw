<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\captcha;

/**
* Слой для работы с кодами подтверждений
*/
class service
{
	private $code;

	protected $config;
	protected $driver;
	protected $request;

	function __construct($driver, $config, $request)
	{
		$this->config  = $config;
		$this->request = $request;
		
		$this->set_driver($driver);
	}
	
	/**
	* Возвращает название используемой библиотеки для отрисовки кодов
	*/
	public function get_driver()
	{
		return $this->driver;
	}
	
	/**
	* Устанавливает новый механизм работы с библиотеками для отрисовки кодов
	*/
	public function set_driver($driver)
	{
		$this->driver = $driver;
	}

	public function __call($method, $args)
	{
		return call_user_func_array(array($this->driver, $method), $args);
	}
	
	/**
	* Создание нового кода
	*/
	public function generate_code()
	{
		static $symbols = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'И', 'К', 'Л', 'М', 'Н', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Э', 'Ю', 'Я', 1, 2, 4, 5, 6, 7, 8, 9);
		
		$symbols_last_index = sizeof($symbols) - 1;
		
		for( $i = 0, $len = mt_rand($this->config['confirm.min_chars'], $this->config['confirm.max_chars']); $i < $len; $i++ )
		{
			$this->code .= $symbols[mt_rand(0, $symbols_last_index)];
		}
		
		// $this->code = strtr(mb_strtoupper(make_random_string(mt_rand($this->config['confirm_min_chars'], $this->config['confirm_max_chars']))), $transform);
		$this->solved = false;
		
		$_SESSION['confirm_code'] = (string) $this->code;
	}
	
	/**
	* Вывод кода
	*/
	public function send()
	{
		if( empty($this->code) )
		{
			$this->generate_code();
		}
		
		$this->driver->send($this->code);
	}
}
