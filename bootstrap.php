<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw;

use fw\core\application;
use fw\core\errorhandler;

define('START_TIME', microtime(true));
define('FW_DIR', __DIR__ . '/../../fw/master/');
define('KORDEN_FW_DIR', __DIR__ . '/');
define('SITE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', false);
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

require(FW_DIR . 'core/application.php');
require(FW_DIR . 'core/autoloader.php');
require(FW_DIR . 'functions.php');
require(KORDEN_FW_DIR . 'functions.php');
require(FW_DIR . 'config.php');
require(KORDEN_FW_DIR . 'config.php');
require(SITE_DIR . '../config.php');

$app = new application($app);
$app['autoloader']->register_namespaces([
		'fw'      => $app['dir.fw'],
		'app'     => $app['dir.app'],
		'Guzzle'  => "{$app['dir.lib']}/guzzle/{$app['version.guzzle']}/Guzzle",
		'Symfony' => "{$app['dir.lib']}/symfony/{$app['version.symfony']}/Symfony",
	])
	->register_pear('Swift', "{$app['dir.lib']}/swiftmailer/{$app['version.swift']}/classes");

require(KORDEN_FW_DIR . 'constants.php');
require(SITE_DIR . '../constants.php');

errorhandler::register($app['errorhandler.options']);
