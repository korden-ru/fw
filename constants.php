<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw;

$app['acm.fw_prefix'] = $app::VERSION . '_korden.fw';

if (false === $app->load_constants($app['acm.fw_prefix']))
{
	$app->set_constants($app['acm.fw_prefix'], [
		/* Таблицы сайта */
		'AUTH_GROUPS_TABLE'          => 'site_auth_groups',
		'AUTH_OPTIONS_TABLE'         => 'site_auth_options',
		'AUTH_ROLES_TABLE'           => 'site_auth_roles',
		'AUTH_ROLES_DATA_TABLE'      => 'site_auth_roles_data',
		'AUTH_USERS_TABLE'           => 'site_auth_users',
		'BANLIST_TABLE'              => 'site_banlist',
		'BANNERS_TABLE'              => 'site_banners',
		'BANNERS_TYPES_TABLE'        => 'site_banners_types',
		'BOTS_TABLE'                 => 'site_bots',
		'COMMENTS_TABLE'             => 'site_comments',
		'CONFIG_TABLE'               => 'site_config',
		'CONFIRM_TABLE'              => 'site_confirm',
		'CRON_TABLE'                 => 'site_cron',
		'FORMS_TABLE'                => 'site_forms',
		'FORM_FIELDS_TABLE'          => 'site_form_fields',
		'FORM_TABS_TABLE'            => 'site_form_tabs',
		'GALLERIES_TABLE'            => 'site_galleries',
		'GALLERY_PHOTOS_TABLE'       => 'site_gallery_photos',
		'GROUPS_TABLE'               => 'site_groups',
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
		'OPENID_IDENTITIES_TABLE'    => 'site_openid_identities',
		'PAGES_TABLE'                => 'site_pages',
		'PAGES_GALLERY_TABLE'        => 'site_pages_gallery',
		'PUBLICATIONS_TABLE'         => 'site_publications',
		'PUBLICATIONS_GALLERY_TABLE' => 'site_publications_gallery',
		'SEO_TABLE'                  => 'site_seo',
		'SESSIONS_TABLE'             => 'site_sessions',
		'SESSIONS_KEYS_TABLE'        => 'site_sessions_keys',
		'SITES_TABLE'                => 'site_sites',
		'USERS_TABLE'                => 'site_users',
		'USER_GROUPS_TABLE'          => 'site_user_groups',
		'VACANCIES_TABLE'            => 'site_vacancies',
		'VACANCY_APPLICATIONS_TABLE' => 'site_vacancy_applications',
	]);
}
