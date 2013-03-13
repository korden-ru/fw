<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw;

$app = [
	/* Настройки кэша */
	'acm.prefix'        => '',
	'acm.shared_prefix' => '',
	'acm.type'          => 'null',
	
	/* Настройки кода подтверждения */
	'captcha.fonts' => ['tremble.ttf'],
	'captcha.type'  => 'gd',
	
	/* Настройки подключения к БД */
	'db.host' => 'localhost',
	'db.port' => false,
	'db.name' => '',
	'db.user' => '',
	'db.pass' => '',
	'db.sock' => '/tmp/mysql.sock',
	'db.pers' => false,
	
	/* Пути к папкам */
	'dir.app'             => SITE_DIR . '../includes',
	'dir.fonts'           => FW_DIR . 'assets/fonts',
	'dir.fw'              => rtrim(FW_DIR, '/'),
	'dir.lib'             => FW_DIR . '../lib',
	'dir.logs'            => SITE_DIR . '../logs',
	'dir.templates.app'   => SITE_DIR . '../templates',
	'dir.templates.cache' => SITE_DIR . '../cache/templates',
	'dir.templates.fw'    => FW_DIR . 'templates',
	
	/* Пути к файлам */
	'file.cron.allowed' => 'cron_allowed',
	'file.cron.running' => 'cron_running',
	
	/* Почтовые ящики */
	'mail.error' => 'korden.fw@ivacuum.ru',
	

	/* Типы страниц */
	'page.types' => [
		'Текстовая'            => 0,
		'Текстовая с галереей' => 1,
		'Текстовая с блоками'  => 2,
		'Блоки'                => 3,
	],
	
	/* Типы публикаций */
	'publication.types' => [
		'articles'     => 1,
		'news'         => 2,
		'publications' => 3,
		'services'     => 4,
	],

	/* Замена доменов на их локальные варианты */
	'request.local_redirect.from' => '',
	'request.local_redirect.to'   => '',
	
	/* Настройки сессий */
	'session.config' => [
		'name'            => 'sid',
		'cookie_path'     => '/',
		'cookie_domain'   => '',
		'cookie_secure'   => false,
		'cookie_httponly' => true,
		'cookie_lifetime' => 0,
		'referer_check'   => false,
		'hash_function'   => 'sha1',
	],
	
	/* Настройки подключения к поисковику sphinx */
	'sphinx.host' => 'localhost',
	'sphinx.port' => false,
	'sphinx.sock' => '/tmp/sphinx.sock',
	
	/* Ссылки */
	'urls' => [
		'register' => '/',
		'signin'   => '/',
		'singout'  => '/',
	],
	
	/* Версии библиотек */
	'version.geocoder' => '1.1.6',
	'version.imagine'  => '0.4.1',
	'version.monolog'  => '1.0.3',
	'version.smarty'   => '3.1.13',
	'version.swift'    => '4.3.0',
];

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
