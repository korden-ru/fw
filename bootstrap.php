<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine;

use engine\cache\factory as cache_factory;
use engine\config\db as config_db;
use engine\core\autoloader;
use engine\core\errorhandler;
use engine\core\profiler;
use engine\core\request;
use engine\core\user;
use engine\db\mysqli as db_mysqli;
// use engine\logger\logger;
// use engine\logger\handlers\db as db_logger;
use engine\template\smarty;
// use Monolog\Handler\NativeMailerHandler;
// use Monolog\Handler\StreamHandler;

session_start();

/**
* Настройки, необходимые для
* функционирования сайта
*/
if( false !== strpos($_SERVER['SERVER_NAME'], '.korden.net') )
{
	error_reporting(E_ALL);
}
else
{
	error_reporting(0);
}

date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('utf-8');

$acp_root_path  = 'acp/';
$site_root_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
$src_root_path  = __DIR__ . '/';

define('ROOT_PATH', $site_root_path);

require($src_root_path . 'core/profiler.php');
require($src_root_path . 'core/autoloader.php');

/* Профайлер подключается первым */
$profiler = new profiler();

require($src_root_path . 'functions.php');
require($src_root_path . 'config.php');

if( file_exists($site_root_path . '../config.php') )
{
	require($site_root_path . '../config.php');
}

$loader = new autoloader($acm_prefix);
$loader->register_namespaces(array(
	'engine'  => __DIR__,
	'Monolog' => __DIR__ . '/lib/monolog/1.0.3/Monolog',
	'app'     => $site_root_path . '../modules',
	'acp'     => $site_root_path . 'acp/includes',
));
$loader->register();

// $log = new logger('main');
// $log->push_handler(new StreamHandler($site_root_path . '../logs/file', logger::DEBUG));
// $log->push_handler(new NativeMailerHandler('src-work@ivacuum.ru', 'Monolog', 'www@bsd.korden.net', logger::DEBUG););
// $log->push_processor(function($record) {
// 	$record['extra']['ary'] = 'My message';
// 	
// 	return $record;
// });
// $log->info('hello');

/* Собственный обработчик ошибок */
errorhandler::register();

$request = new request();

/* Инициализация кэша */
$factory = new cache_factory($acm_type, $acm_prefix);
$cache   = $factory->get_service();

$db = new db_mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsock, $dbpers);
// $log->push_handler(new db_logger($db));
// $log->info('Привет!');

if( false === strpos($_SERVER['SERVER_NAME'], '.korden.net') )
{
	/* Принудительная установка кодировки для хостинг-провайдеров */
	$db->query('SET NAMES utf8');
}

/* Инициализация классов */
$template = new smarty();
$user     = new user();
$config   = new config_db($site_info, CONFIG_TABLE);

$template->assign('cfg', $config);
$template->assign('metaVersion', 1);
