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
	'acm.prefix' => 'src_3.6-dev',
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

define('SQL_PREFIX', 'site_');

/**
* Константы
* apc_delete($app['acm.prefix'] . '_constants');
*/
if( false === load_constants() )
{
	set_constants(array(
		/* Таблицы сайта */
		'BANNERS_TABLE'              => 'site_banners',
		'BANNERS_TYPES_TABLE'        => 'site_banners_types',
		'CONFIG_TABLE'               => 'site_config',
		'CRON_TABLE'                 => 'site_cron',
		'FORMS_TABLE'                => 'site_forms',
		'FORM_FIELDS_TABLE'          => 'site_form_fields',
		'FORM_TABS_TABLE'            => 'site_form_tabs',
		'GALLERIES_TABLE'            => 'site_galleries',
		'GALLERY_PHOTOS_TABLE'       => 'site_gallery_photos',
		'I18N_TABLE'                 => 'site_i18n',
		'IMAGE_WATERMARKS_TABLE'     => 'site_image_watermarks',
		'LANGUAGES_TABLE'            => 'site_languages',
		'LOGS_TABLE'                 => 'site_logs',
		'MAILLIST_TABLE'             => 'site_maillist',
		'MAILLIST_GROUPS_TABLE'      => 'site_maillist_groups',
		'MAILLIST_GROUP_USERS_TABLE' => 'site_maillist_group_users',
		'MAILLIST_SIGNATURE_TABLE'   => 'site_maillist_signature',
		'MAILLIST_SPOOL_TABLE'       => 'site_maillist_spool',
		'MENUS_TABLE'                => 'site_menus',
		'MODULES_TABLE'              => 'site_modules',
		'PAGES_TABLE'                => 'site_pages',
		'PAGES_GALLERY_TABLE'        => 'site_pages_gallery',
		'PUBLICATIONS_TABLE'         => 'site_publications',
		'PUBLICATIONS_GALLERY_TABLE' => 'site_publications_gallery',
		'SEO_TABLE'                  => 'site_seo',
		'SITES_TABLE'                => 'site_sites',
		'VACANCIES_TABLE'            => 'site_vacancies',
		'VACANCY_APPLICATIONS_TABLE' => 'site_vacancy_applications',
	));
}
