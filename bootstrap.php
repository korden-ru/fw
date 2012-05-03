<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine;

use engine\core\application;
use engine\core\errorhandler;

/**
* Настройки, необходимые для
* функционирования сайта
*/
define('FW_DIR', __DIR__ . '/');
define('SITE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

date_default_timezone_set('Europe/Moscow');
error_reporting(false !== strpos($_SERVER['SERVER_NAME'], '.korden.net') ? E_ALL : 0);
mb_internal_encoding('utf-8');

require(FW_DIR . 'core/profiler.php');
require(FW_DIR . 'core/application.php');
require(FW_DIR . 'core/autoloader.php');
require(FW_DIR . 'functions.php');
require(FW_DIR . 'config.php');

if( file_exists(SITE_DIR . '../config.php') )
{
	require(SITE_DIR . '../config.php');
}

$app = new application($app);

$app['autoloader']->register_namespaces(array(
	'engine'  => __DIR__,
	'Monolog' => __DIR__ . '/../lib/monolog/1.0.3/Monolog',
	'app'     => SITE_DIR . '../modules',
	'acp'     => SITE_DIR . 'acp/includes',
));

$app['db']->_set_cache($app['cache']);

/* Собственный обработчик ошибок */
errorhandler::register();

if( false === strpos($app['request']->server('SERVER_NAME'), '.korden.net') )
{
	/* Принудительная установка кодировки для хостинг-провайдеров */
	$app['db']->query('SET NAMES utf8');
}

$app['template']->assign('cfg', $app['config']);
