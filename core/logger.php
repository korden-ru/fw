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
    public function push_handler($handler)
    {
		$this->pushHandler($handler);
    }
}
