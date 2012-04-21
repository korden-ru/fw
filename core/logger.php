<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

use Monolog\Logger as base_logger;

/**
* Логирование событий
* Используется библиотека Monolog
*/
class logger extends base_logger
{
	/**
	* Название уровня логирования
	*/
	public function get_level_name($level)
	{
		return $this->getLevelName($level);
	}
	
	/**
	* Название канала
	*/
	public function get_name()
	{
		return $this->getName();
	}
	
	/**
	* Проверка: обрабатываются ли события определенного уровня
	*/
	public function is_handling($level)
	{
		return $this->isHandling($level);
	}
	
	/**
	* Извлечение обработчика
	*/
	public function pop_handler()
	{
		return $this->popHandler();
	}
	
	/**
	* Извлечение процессора
	*/
	public function pop_processor()
	{
		return $this->popProcessor();
	}
	
	/**
	* Добавление обработчика в верхушку стека
	*/
    public function push_handler($handler)
    {
		$this->pushHandler($handler);
    }
	
	/**
	* Добавление процессора в верхушку стека
	*/
	public function push_processor($callback)
	{
		$this->pushProcessor($callback);
	}
}
