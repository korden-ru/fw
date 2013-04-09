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
		'BANNERS_TABLE'              => 'site_banners',
		'BANNERS_TYPES_TABLE'        => 'site_banners_types',
		'GALLERIES_TABLE'            => 'site_galleries',
		'GALLERY_PHOTOS_TABLE'       => 'site_gallery_photos',
		'LOGS_TABLE'                 => 'site_logs',
		'MAILLIST_TABLE'             => 'site_maillist',
		'MAILLIST_GROUPS_TABLE'      => 'site_maillist_groups',
		'MAILLIST_GROUP_USERS_TABLE' => 'site_maillist_group_users',
		'MAILLIST_SIGNATURE_TABLE'   => 'site_maillist_signature',
		'MAILLIST_SPOOL_TABLE'       => 'site_maillist_spool',
		'MODULES_TABLE'              => 'site_modules',
		'PAGES_GALLERY_TABLE'        => 'site_pages_gallery',
		'PUBLICATIONS_TABLE'         => 'site_publications',
		'PUBLICATIONS_GALLERY_TABLE' => 'site_publications_gallery',
		'SEO_TABLE'                  => 'site_seo',
		'VACANCIES_TABLE'            => 'site_vacancies',
		'VACANCY_APPLICATIONS_TABLE' => 'site_vacancy_applications',
	]);
}
