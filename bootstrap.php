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
// use engine\logger\logger;
// use engine\logger\handlers\db as db_logger;
// use Monolog\Handler\NativeMailerHandler;
// use Monolog\Handler\StreamHandler;

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
	'Monolog' => __DIR__ . '/lib/monolog/1.0.3/Monolog',
	'app'     => SITE_DIR . '../modules',
	'acp'     => SITE_DIR . 'acp/includes',
));

// $log = new logger('main');
// $log->push_handler(new StreamHandler(SITE_DIR . '../logs/file', logger::DEBUG));
// $log->push_handler(new NativeMailerHandler('src-work@ivacuum.ru', 'Monolog', 'www@bsd.korden.net', logger::DEBUG););
// $log->push_processor(function($record) {
// 	$record['extra']['ary'] = 'My message';
// 	
// 	return $record;
// });
// $log->info('hello');

/* Собственный обработчик ошибок */
errorhandler::register();

$request = $app['request'];
$db = $app['db'];
// $log->push_handler(new db_logger($db));
// $log->info('Привет!');

if( false === strpos($app['request']->server('SERVER_NAME'), '.korden.net') )
{
	/* Принудительная установка кодировки для хостинг-провайдеров */
	$db->query('SET NAMES utf8');
}

/* Инициализация классов */
$template = $app['template'];
$user     = $app['user'];
$config   = $app['config'];

$app['template']->assign('cfg', $app['config']);
