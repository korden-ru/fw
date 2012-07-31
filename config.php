<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
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
	'acm.prefix' => 'src_3.5-dev',
	'acm.type'   => 'null',
	
	/* Типы страниц */
	'page.types' => array(
		'Текстовая'            => 0,
		'Текстовая с галереей' => 1,
		'Текстовая с блоками'  => 2,
		'Блоки'                => 3,
	),
	
	/* Типы публикаций */
	'publication.types' => array(
		'articles'     => 1,
		'news'         => 2,
		'publications' => 3,
		'services'     => 4,
	),
);

define('SQL_PREFIX', 'tcms_');

/**
* Константы
* apc_delete($app['acm.prefix'] . '_constants');
*/
if( false === load_constants() )
{
	set_constants(array(
		/* Таблицы сайта */
		'BANNERS_TABLE'           => 'tcms_banners',
		'BANNERS_TYPES_TABLE'     => 'tcms_banners_types',
		'CONFIG_TABLE'            => 'tcms_config',
		'CRON_TABLE'              => 'tcms_cron',
		'I18N_TABLE'              => 'tcms_i18n',
		'IMAGE_WATERMARKS_TABLE'  => 'tcms_image_watermarks',
		'LANGUAGES_TABLE'         => 'tcms_languages',
		'LOGS_TABLE'              => 'tcms_logs',
		'MENUS_TABLE'             => 'tcms_menus',
		'PAGES_TABLE'             => 'tcms_pages',
		'SITES_TABLE'             => 'tcms_sites',
	));
}
