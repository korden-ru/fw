<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine;

session_start(); 

/**
* Настройки, необходимые для
* функционирования сайта
*/
$_SESSION['user_login'] = ( isset($_SESSION['user_login']) ) ? $_SESSION['user_login'] : '';
$_SESSION['user_passwd'] = ( isset($_SESSION['user_passwd']) ) ? $_SESSION['user_passwd'] : '';

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
$profiler = new core\profiler();

require($src_root_path . 'functions.php');
require($src_root_path . 'config.php');

if( file_exists($site_root_path . '../config.php') )
{
	require($site_root_path . '../config.php');
}

$loader = new core\autoloader($acm_prefix);
$loader->register_namespaces(array(
	'engine' => __DIR__,
	'app'    => $site_root_path . '../modules',
	'acp'    => $site_root_path . 'acp/includes',
));
$loader->register();

/* Собственный обработчик ошибок */
core\errorhandler::register();

$request = new core\request();

/* Инициализация кэша */
$factory = new cache\factory($acm_type, $acm_prefix);
$cache   = $factory->get_service();

$db = new db\mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsock, $dbpers);

if( false === strpos($_SERVER['SERVER_NAME'], '.korden.net') )
{
	/* Принудительная установка кодировки для хостинг-провайдеров */
	$db->query('SET NAMES utf8');
}

/* Инициализация классов */
$template = new template\smarty();
$user     = new core\user();
$config   = new config\db($site_info, CONFIG_TABLE);

$template->assign('cfg', $config);
$template->assign('metaVersion', 1);
