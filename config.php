<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine;

$app = array(
	/* Настройки подключения к БД */
	'db.host' => 'localhost',
	'db.port' => false,
	'db.name' => '',
	'db.user' => '',
	'db.pass' => '',
	'db.sock' => '',
	'db.pers' => false,
	
	/* Настройки кэша */
	'acm.prefix' => 'src',
	'acm.type'   => 'null',
	
	/* Типы страниц */
	'page.types' => array(
		'Текстовая'            => 0,
		'Текстовая с галереей' => 1,
		'Текстовая с блоками'  => 2,
		'Блоки'                => 3,
	),
);

/* Настройки подключения к БД */
$dbhost = 'localhost';
$dbport = false;
$dbname = '';
$dbuser = '';
$dbpass = '';
$dbsock = '';
$dbpers = false;

/* Настройки кэша */
$acm_prefix = 'src';
$acm_type   = 'null';

/**
* Типы страниц
*/
$_MODULE_TYPEPAGES = array(
	'Текстовая'            => 0,
	'Текстовая с галереей' => 1,
	'Текстовая с блоками'  => 2,
	'Блоки'                => 3,
);

define('SQL_PREFIX', 'tcms_');

/**
* Константы
* apc_delete($acm_prefix . '_constants');
*/
if( false === load_constants() )
{
	set_constants(array(
		/* Таблицы сайта */
		'CONFIG_TABLE'            => 'tcms_config',
		'CRON_TABLE'              => 'tcms_cron',
		'I18N_TABLE'              => 'tcms_i18n',
		'IMAGE_WATERMARKS_TABLE'  => 'tcms_image_watermarks',
		'LANGUAGES_TABLE'         => 'tcms_languages',
		'PAGES_TABLE'             => 'tcms_pages',
		'SITES_TABLE'             => 'tcms_sites',
	));
}
