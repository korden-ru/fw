<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw;

$app = array_merge($app, [
	/* Пути к папкам */
	'dir.fw' => [rtrim(KORDEN_FW_DIR, '/'), rtrim(FW_DIR, '/')],
	
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
]);

/* Настройки обработчика ошибок */
$app['errorhandler.options']['email.error'] = 'korden.fw@ivacuum.ru';
