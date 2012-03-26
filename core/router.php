<?php
/**
*
* @package cms.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

/**
* Маршрутизатор запросов
*/
class router
{
	public $format;
	public $handler;
	public $method;
	public $page = 'index';
	public $page_link = array();
	public $page_row;
	public $site_id;
	public $url;
	
	protected $namespace;
	protected $params = array();
	protected $params_count;

	function __construct($url = '', $namespace = '\\app\\')
	{
		global $config, $user;
		
		$this->format    = $config['router_default_extension'];
		$this->namespace = $namespace;
		$this->page      = $config['router_directory_index'];
		
		$url = ( $url ) ?: htmlspecialchars_decode($user->page);
		
		if( !$url )
		{
			redirect(ilink());
		}
		
		/* Поиск сайта */
		if( false === $this->site_id = $this->get_site_id($user->domain, $user->lang['.']) )
		{
			trigger_error('Сайт не найден');
		}
		
		if( false !== $query_string_pos = strpos($url, '?') )
		{
			$url = substr($url, 0, $query_string_pos);
		}
		
		$this->url = trim($url, '/');
		$ary = pathinfo($this->url);
		
		if( isset($ary['extension']) )
		{
			/* Обращение к странице */
			if( !in_array($ary['extension'], explode(';', $config['router_allowed_extensions']), true) )
			{
				trigger_error('PAGE_NOT_FOUND');
			}
			
			$this->format = $ary['extension'];
			$this->params = ( $ary['dirname'] != '.' ) ? explode('/', $ary['dirname']) : array();
			$this->page   = $ary['filename'];
			$this->url    = ( $ary['dirname'] != '.' ) ? $ary['dirname'] : '';
		}
		elseif( substr($url, -1) != '/' )
		{
			/**
			* Обращение к странице без расширения
			* Проверяем, можно ли обращаться к страницам без расширения
			*/
			if( in_array('', explode(';', $config['router_allowed_extensions']), true) )
			{
				$this->params = ( $ary['dirname'] != '.' ) ? explode('/', $ary['dirname']) : array();
				$this->page   = $ary['filename'];
			}
			else
			{
				/* Перенаправление на одноименный каталог */
				redirect(ilink($this->url));
			}
		}
		elseif( $this->url )
		{
			/* Обращение к каталогу */
			$this->params = explode('/', $this->url);
		}
		
		$this->params_count = sizeof($this->params);
	}
	
	/**
	* Параметры URL
	*/
	public function get_params()
	{
		return $this->params;
	}
	
	/**
	* Количество параметров в URL
	*/
	public function get_params_count()
	{
		return $this->params_count;
	}
	
	/**
	* Обработка URL и загрузка необходимого обработчика
	*/
	public function handle_request()
	{
		global $config;
		
		$dynamic_handle = false;
		$handler_name = $handler_method = '';
		$parent_id = 0;

		/**
		* /[index.html]
		* /[объявление.html]
		*/
		if( empty($this->params) )
		{
			$row = $this->get_page_row_by_url($this->page, false, $parent_id);
			
			if( $row['page_handler'] && $row['handler_method'] )
			{
				$handler_name   = $row['page_handler'];
				$handler_method = $row['handler_method'];
			}
			
			if( $this->page != $config['router_directory_index'] )
			{
				$this->page_link[] = ( $this->format ) ? sprintf('%s.%s', $this->page, $this->format) : $this->page;
			}
			else
			{
				$this->page_link[] = '';
			}

			if( $row['page_url'] != '*' )
			{
				navigation_link(ilink($this->page_link[0]), $row['page_name'], $row['page_image']);
			}
		}
		
		/**
		* /[игры/diablo2]/скриншоты.html
		* /[users/a/admin]/posts.html
		*/
		for( $i = 0; $i < $this->params_count; $i++ )
		{
			$ary = $this->get_page_row_by_url($this->params[$i], true, $parent_id);

			/**
			* /[новости/2011]/07/14/обновление.html
			*
			* Причем существует только "новости", остальные
			* параметры пересылаются обработчику
			*/
			if( $i > 0 && !$ary && $handler_name && $handler_method && $handler_method != 'static_page' )
			{
				$dynamic_handle = true;
				break;
			}
			elseif( !$ary )
			{
				if( isset($row) && $row['page_redirect'] )
				{
					redirect(ilink($row['page_redirect']), 301);
				}
				
				trigger_error('PAGE_NOT_FOUND');
			}
			
			$row = $ary;
			
			if( $row['is_dir'] )
			{
				if( $row['page_handler'] && $row['handler_method'] )
				{
					$handler_name   = $row['page_handler'];
					$handler_method = $row['handler_method'];
				}
				else
				{
					$handler_method = 'static_page';
				}
			}
			
			$this->page_link[] = $this->params[$i];
			
			$parent_id = (int) $row['page_id'];
			
			if( $row['page_url'] != '*' )
			{
				navigation_link(ilink(implode('/', $this->page_link)), $row['page_name'], $row['page_image']);
				
				unset($this->params[$i]);
			}
		}
		
		/**
		* /ucp/[login.html]
		*/
		if( $this->params_count > 0 && !$dynamic_handle && false != $ary = $this->get_page_row_by_url($this->page, false, $parent_id) )
		{
			if( $this->page != $config['router_directory_index'] || $ary['page_url'] != '*' )
			{
				$row = $ary;
			
				if( $row['page_handler'] && $row['handler_method'] )
				{
					$handler_name   = $row['page_handler'];
					$handler_method = $row['handler_method'];
				}
				else
				{
					$handler_method = 'static_page';
				}

				if( $this->page != $config['router_directory_index'] )
				{
					$this->page_link[] = ( $this->format ) ? sprintf('%s.%s', $this->page, $this->format) : $this->page;
				}

				if( $row['page_url'] != '*' )
				{
					navigation_link(ilink(implode('/', $this->page_link)), $row['page_name'], $row['page_image']);
				}
			}
		}
		
		if( !$row )
		{
			/* На сайте еще нет ни одной страницы */
			trigger_error('PAGE_NOT_FOUND');
		}
		
		if( !in_array($this->format, explode(';', $row['page_formats']), true) )
		{
			trigger_error('PAGE_NOT_FOUND');
		}

		$row['site_id'] = (int) $row['site_id'];

		/* Сбрасывание счетчика индексов */
		$this->params = array_values($this->params);
		$this->params_count = sizeof($this->params);
		
		$this->page_row = $row;
		
		/* Статичная страница */
		if( !$handler_name || !$handler_method )
		{
			/* Нужно ли переадресовать на другую страницу */
			if( $row['page_redirect'] )
			{
				redirect(ilink($row['page_redirect']), 301);
			}
			
			return $this->load_handler('models\\page', 'static_page');
		}
		elseif( $handler_method == 'static_page' && $row['page_redirect'] )
		{
			redirect(ilink($row['page_redirect']), 301);
		}
		
		return $this->load_handler($handler_name, $handler_method, $this->params);
	}

	/**
	* Загрузка модуля
	*/
	protected function load_handler($handler, $method, $params = array(), $redirect = false)
	{
		$class_name = ( 0 !== strpos($handler, '\\') ) ? $this->namespace . $handler : $handler;
		
		$this->handler = new $class_name;
		$this->method  = $method;
		
		if( !$this->load_handler_with_params($params) )
		{
			if( $redirect )
			{
				redirect($redirect);
			}
			
			return false;
		}
		
		return true;
	}
	
	/**
	* Загрузка модуля с параметрами
	*/
 	protected function load_handler_with_params($params = array())
	{
		global $config, $request;
		
		$concrete_method = sprintf('%s_%s', $this->method, $request->method);

		/**
		* Проверка существования необходимого метода у обработчика
		*/
		if( !method_exists($this->handler, $concrete_method) && !method_exists($this->handler, $this->method) )
		{
			if( $config['router_send_status_codes'] )
			{
				/**
				* API-сайт должен отправлять соответствующие коды состояния HTTP
				*/
				if( $request->method == 'get' || !method_exists($this->handler, $this->method . '_get') )
				{
					send_status_line(501, 'Not Implemented');
				}
				else
				{
					send_status_line(405, 'Method Not Allowed');
				}
				
				return false;
			}
			else
			{
				/* Обычный сайт может сразу возвращать 404 Not Found */
				send_status_line(404, 'Not Found');
				return false;
			}
		}
		
		$full_url = $this->url . (($this->page != $config['router_directory_index']) ? (($this->format) ? sprintf('/%s.%s', $this->page, $this->format) : $this->page) : '');
		
		/* Параметры обработчика */
		$this->handler->data     = $this->page_row;
		$this->handler->format   = $this->format;
		$this->handler->full_url = $full_url;
		$this->handler->method   = $this->method;
		$this->handler->page     = $this->page;
		$this->handler->params   = $params;
		$this->handler->url      = implode('/', $this->page_link);
		
		/* Настройка обработчика */
		$this->handler->obtain_handlers_urls();
		$this->handler->set_default_template();
		$this->handler->set_site_menu();
		$this->handler->set_page_data();
		$this->handler->set_appropriate_content_type();
		
		/* Предустановки */
		if( method_exists($this->handler, '_setup') )
		{
			call_user_func(array($this->handler, '_setup'));
		}
		
		if( method_exists($this->handler, $concrete_method) )
		{
			/**
			* Попытка вызвать метод с суффиксом в виде HTTP метода
			* GET index -> index_get
			* PUT single -> single_put
			*/
			call_user_func_array(array($this->handler, $concrete_method), $params);
			$this->call_with_format($concrete_method, $params);
		}
		else
		{
			call_user_func_array(array($this->handler, $this->method), $params);
			$this->call_with_format($this->method, $params);
		}
		
		$this->handler->page_header();
		$this->handler->page_footer();
		
		return true;
	}
	
	/**
	* Попытка вызвать метод с суффиксом в виде формата документа
	*/
	protected function call_with_format($method, $params)
	{
		if( $this->format )
		{
			$method = sprintf('%s_%s', $method, $this->format);
			
			if( method_exists($this->handler, $method) )
			{
				call_user_func_array(array($this->handler, $method), $params);
			}
		}
	}
	
	/**
	* Данные страницы
	*/
	protected function get_page_row_by_url($page_url, $is_dir = 1, $parent_id = 0)
	{
		global $db;
		
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				parent_id = ' . $db->check_value($parent_id) . '
			AND
				site_id = ' . $db->check_value($this->site_id) . '
			AND
				' . $db->in_set('page_url', array($page_url, '*')) . '
			AND
				is_dir = ' . $db->check_value($is_dir) . '
			AND
				page_enabled = 1
			ORDER BY
				LENGTH(page_url) DESC';
		$db->query_limit($sql, 1);
		$row = $db->fetchrow();
		$db->freeresult();
		
		/* Загрузка блока */
		if( !$row && !$is_dir && $parent_id )
		{
			$row = get_page_block($page_url, $parent_id, 'pages');
		}
		
		return $row;
	}

	/**
	* Возврат данных сайта
	*
	* Главным образом это проверка сайта (и определенной локализации) на существование
	*/
	protected function get_site_id($domain, $language)
	{
		global $site_info;
		
		if( empty($site_info) )
		{
			return false;
		}
		
		setlocale(LC_ALL, $site_info['locale']);
		
		return (int) $site_info['id'];
	}
}
