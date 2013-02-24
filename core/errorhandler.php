<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\core;

/**
* Обработчик ошибок
*/
class errorhandler
{
	static public function handle_error($type, $text, $file, $line)
	{
		global $app;
		
		/**
		* Выходим, если проверка отключена через @
		*/
		if( error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE )
		{
			return;
		}
		
		$file = str_replace($app['request']->server('DOCUMENT_ROOT'), '', $file);
		
		switch( $type )
		{
			/**
			* Ошибка/предупреждение
			*/
			case E_NOTICE:
			case E_WARNING:
			
				$app['profiler']->log_error($text, $line, $file);
				return;

			break;
			/**
			* Критическая ошибка
			* Если sql, то выводим как есть
			*/
			case E_USER_ERROR:
			
				if( defined('IN_SQL_ERROR') )
				{
					global $error_ary;
					
					self::log_mail($error_ary);
				}
				else
				{
					self::log_mail($text);
				}

				send_status_line(503, 'Service Unavailable');
				garbage_collection(false);

				echo '<!DOCTYPE html>';
				echo '<html lang="ru">';
				echo '<head>';
				echo '<meta charset="utf-8">';
				echo '<meta name="robots" content="noindex, nofollow">';
				echo '<title>Сервис временно недоступен</title>';
				echo '</head>';
				echo '<body>';
				echo '<h1>Сервис временно недоступен</h1>';
				echo '<p>Отчет о произошедшей ошибке отправлен администратору.</p>';
				echo '<p>Приносим извинения за доставленные неудобства.</p>';
				
				if( $_SERVER['REMOTE_ADDR'] == '95.31.213.80' || $_SERVER['REMOTE_ADDR'] == '79.195.20.190' || $_SERVER['REMOTE_ADDR'] == '192.168.1.1' )
				{
					if( defined('IN_SQL_ERROR') )
					{
						echo '<h2>Ошибка в SQL запросе</h2>';
						echo '<ul>';
						echo '<li>Код ошибки: <b>' . $error_ary['code'] . '</b></li>';
						echo '<li>Текст ошибки: <b>' . $error_ary['text'] . '</b></li>';
						echo '</ul>';
						echo '<code>' . $error_ary['sql'] . '</code>';
					}
				}
				
				echo '</body>';
				echo '</html>';
				exit;

			break;
			/**
			* Пользовательская ошибка
			* Выводим, используя оформление сайта
			*/
			case E_USER_NOTICE:
			case E_USER_WARNING:

				if( !empty($app['router']) && is_object($app['router']->handler) )
				{
					$handler = $app['router']->handler;
				}
				else
				{
					$handler = new \app\models\page();
					$handler->data['site_id'] = $app['site_info']['id'];
					$handler->format = !empty($app['router']) ? $app['router']->format : $app['config']['router.default_extension'];
					
					$handler->_set_cache($app['cache'])
						->_set_config($app['config'])
						->_set_db($app['db'])
						->_set_profiler($app['profiler'])
						->_set_request($app['request'])
						->_set_template($app['template'])
						->_set_user($app['user'])
						->set_site_menu();
					
					/* Предустановки */
					if( method_exists($handler, '_setup') )
					{
						call_user_func(array($handler, '_setup'));
					}
				}
				
				/* Запрет индексирования страницы */
				$handler->data['page_noindex'] = 1;

				/**
				* Необходимо выдать HTTP/1.0 404 Not Found,
				* если сообщение об отсутствии данных или ошибке
				*/
				preg_match('#NOT_FOUND$#', $text, $matches);

				if( !empty($matches) || 0 === strpos($text, 'ERR_') )
				{
					send_status_line(404, 'Not Found');
					$text = 'Страница не найдена';
					// self::log_mail('Page http://' . $user->domain . $user->page . ' not found', '404 Not Found');
				}
			
				$app['template']->assign(array(
					'page' => $handler->data,
				
					'MESSAGE_TEXT'  => $text
				));
			
				$app['template']->file = 'message_body.html';
			
				$handler->page_header();
				$handler->page_footer();

			break;
		}
		
		/**
		* Обработчик ошибок PHP не будет задействован, если не возвратить false
		* Возвращаем false, чтобы необработанные ошибки были помещены в журнал
		*/
		return false;
	}

	/**
	* Перехват критических ошибок
	*/
	static public function handle_fatal_error()
	{
		if( $error = error_get_last() )
		{
			switch( $error['type'] )
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
				
					self::log_mail('Fatal error: ' . $error['message']);
					
					if( $_SERVER['REMOTE_ADDR'] != '79.175.20.190' && $_SERVER['REMOTE_ADDR'] != '95.31.213.80' && $_SERVER['REMOTE_ADDR'] != '192.168.1.1' )
					{
						return;
					}

					$error['file'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $error['file']);

					printf('<br><br><b style="color: red;">***</b> <b style="white-space: pre-line;">%s</b> on line <b>%d</b> in file <b>%s</b>.<br />', $error['message'], $error['line'], $error['file']);

					if( function_exists('xdebug_print_function_stack') )
					{
						echo '<pre>', xdebug_print_function_stack(), '</pre>';
					}

				break;
			}
		}
	}
	
	/**
	* Уведомление администратора о произошедшей ошибке
	*/
	static public function log_mail($text, $title = '')
	{
		global $app;
		
		$call_stack = '';
		$text       = is_array($text) ? print_r($text, true) : $text;
		
		if( !$title )
		{
			$title = defined('IN_SQL_ERROR') ? 'E_USER_ERROR_SQL' : 'E_USER_ERROR';
		}
		
		if( function_exists('xdebug_print_function_stack') )
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace(array('/srv/www/vhosts'), array(''), ob_get_clean());
		}
		
		mail('src-work@ivacuum.ru', $title, $text . "\n" . $call_stack . print_r($_SESSION, true) . "\n" . print_r($_SERVER, true) . "\n" . print_r($_REQUEST, true), sprintf("From: %s@%s\r\n", $app['user']->domain, gethostname()));
	}

	/**
	* Регистрация обработчика
	*/
	static public function register()
	{
		set_error_handler(array(new self, 'handle_error'));
		register_shutdown_function(array(new self, 'handle_fatal_error'));
	}
	
	/**
	* Возврат обработчика по умолчанию
	*/
	static public function unregister()
	{
		restore_error_handler();
	}
}
