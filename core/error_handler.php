<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

/**
* Обработчик ошибок
*/
class error_handler
{
	static public function handle_error($type, $text, $file, $line)
	{
		global $profiler, $request;
		
		/* Выходим, если проверка отключена через @ */
		/*
		if( error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE )
		{
			return;
		}
		*/
		
		$file = str_replace($request->server('DOCUMENT_ROOT'), '', $file);
		
		switch( $type )
		{
			/**
			* Ошибка/предупреждение
			*/
			case E_NOTICE:
			case E_WARNING:
			
				$profiler->log_error($text, $line, $file);
				return;

			break;
			/**
			* Критическая ошибка
			* Если sql, то выводим как есть
			*/
			case E_USER_ERROR:
			
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

				global $config, $router, $site_info, $template, $user;

				if( !empty($router) && is_object($router->handler) )
				{
					$handler =& $router->handler;
				}
				else
				{
					$handler = new \app\models\page();
					$handler->data['site_id'] = $site_info['id'];
					$handler->set_site_menu();
					$handler->format = ( !empty($router) ) ? $router->format : $config['router_default_extension'];
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
				}
			
				$template->assign(array(
					'page' => $handler->data,
				
					'MESSAGE_TEXT'  => $text
				));
			
				$template->file = 'message_body.html';
			
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
	* Регистрация обработчика
	*/
	static public function register()
	{
		set_error_handler(array(new self, 'handle_error'));
	}
	
	/**
	* Возврат обработчика по умолчанию
	*/
	static public function unregister()
	{
		restore_error_handler();
	}
}
