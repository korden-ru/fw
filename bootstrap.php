<?php
/**
*
* @package cms.korden.net
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
$src_root_path  = dirname(__FILE__) . '/';

define('ROOT_PATH', $site_root_path);

autoloader::register();

/* Профайлер подключается первым */
$profiler = new core\profiler();

require($src_root_path . 'functions.php');
require($src_root_path . 'config.php');

if( file_exists($site_root_path . '../config.php') )
{
	require($site_root_path . '../config.php');
}

/* Собственный обработчик ошибок */
core\error_handler::register();

$request = new core\request();

/* Инициализация кэша */
$factory = new cache\factory($acm_type, $acm_prefix);
$cache   = $factory->get_service();

/* Инициализация классов */
$db       = new db\mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsock, $dbpers);
$template = new template\smarty();
$user     = new core\user();
$config   = new config\db($site_info, CONFIG_TABLE);

// $db->query('SET NAMES utf8');

$template->assign('cfg', $config);
$template->assign('metaVersion', 1);

/**
* Автозагрузчик классов
*/
class autoloader
{
	/**
	* Загрузка класса
	*/
	static public function autoload($class)
	{
		global $site_root_path, $src_root_path;
		
		if( strpos($class, '\\') === false )
		{
			return;
		}
		
		list($prefix, $filename) = explode('/', str_replace('\\', '/', $class), 2);
		
		if( $prefix == 'engine' && file_exists($src_root_path . $filename . '.php') )
		{
			require($src_root_path . $filename . '.php');
			return true;
		}
		elseif( defined('IN_ACP') && $prefix == 'app' && file_exists($site_root_path . 'acp/includes/' . $filename . '.php') )
		{
			require($site_root_path . 'acp/includes/' . $filename . '.php');
			return true;
		}
		elseif( $prefix == 'app' && file_exists($site_root_path . '../modules/' . $filename . '.php') )
		{
			require($site_root_path . '../modules/' . $filename . '.php');
			return true;
		}
		
		return false;
	}
	
	/**
	* Регистрация загрузчика
	*/
	static public function register()
	{
		spl_autoload_register(array(new self, 'autoload'));
	}
}
