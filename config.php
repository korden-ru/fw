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
	'acm.prefix' => 'src_3.5',
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
		'BANNERS_TABLE'              => 'tcms_banners',
		'BANNERS_TYPES_TABLE'        => 'tcms_banners_types',
		'CONFIG_TABLE'               => 'tcms_config',
		'CRON_TABLE'                 => 'tcms_cron',
		'FORMS_TABLE'                => 'tcms_forms',
		'FORM_FIELDS_TABLE'          => 'tcms_form_fields',
		'FORM_TABS_TABLE'            => 'tcms_form_tabs',
		'GALLERIES_TABLE'            => 'tcms_galleries',
		'GALLERY_PHOTOS_TABLE'       => 'tcms_gallery_photos',
		'I18N_TABLE'                 => 'tcms_i18n',
		'IMAGE_WATERMARKS_TABLE'     => 'tcms_image_watermarks',
		'LANGUAGES_TABLE'            => 'tcms_languages',
		'LOGS_TABLE'                 => 'tcms_logs',
		'MAILLIST_TABLE'             => 'tcms_maillist',
		'MAILLIST_GROUPS_TABLE'      => 'tcms_maillist_groups',
		'MAILLIST_GROUP_USERS_TABLE' => 'tcms_maillist_group_users',
		'MAILLIST_SIGNATURE_TABLE'   => 'tcms_maillist_signature',
		'MAILLIST_SPOOL_TABLE'       => 'tcms_maillist_spool',
		'MENUS_TABLE'                => 'tcms_menus',
		'MODULES_TABLE'              => 'tcms_modules',
		'PAGES_TABLE'                => 'tcms_pages',
		'PAGES_GALLERY_TABLE'        => 'tcms_pages_gallery',
		'PUBLICATIONS_TABLE'         => 'tcms_publications',
		'PUBLICATIONS_GALLERY_TABLE' => 'tcms_publications_gallery',
		'SEO_TABLE'                  => 'tcms_seo',
		'SITES_TABLE'                => 'tcms_sites',
		'VACANCIES_TABLE'            => 'tcms_vacancies',
		'VACANCY_APPLICATIONS_TABLE' => 'tcms_vacancy_applications',
	));
}
