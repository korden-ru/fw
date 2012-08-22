<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\models;

/**
* Страница сайта
*/
class page
{
	public $data;
	public $format;
	public $full_url;
	public $handlers_urls = array();
	public $method;
	public $page;
	public $params;
	public $url;
	public $urls = array();
	
	protected $cache;
	protected $config;
	protected $db;
	protected $form;
	protected $profiler;
	protected $request;
	protected $template;
	protected $user;
	
	function __construct()
	{
	}
	
	public function _set_cache($cache)
	{
		$this->cache = $cache;
		
		return $this;
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
	}
	
	public function _set_form($form)
	{
		$this->form = $form;
		
		return $this;
	}
	
	public function _set_profiler($profiler)
	{
		$this->profiler = $profiler;
		
		return $this;
	}
	
	public function _set_request($request)
	{
		$this->request = $request;
		
		return $this;
	}
	
	public function _set_template($template)
	{
		$this->template = $template;
		$this->template->registerPlugin('function', 'url_for', array($this, 'smarty_function_url_for'));
		
		return $this;
	}
	
	public function _set_user($user)
	{
		$this->user = $user;
		
		return $this;
	}
	
	/**
	* Добавление параметров к ссылке
	*
	* /ссылка/ + id=100              => /ссылка/?id=100
	* /ссылка.html + id=100          => /ссылка.html?id=100
	* /ссылка/?goto=%2F + id=100     => /ссылка/?goto=%2F&id=100
	* /ссылка.html?goto=%2F + id=100 => /ссылка.html?goto=%2F&id=100
	*/
	public function append_link_params($query_string, $url = false)
	{
		$url = $url ? ilink($url) : ilink($this->url);
		
		return false !== strpos($url, '?') ? sprintf('%s&%s', $url, $query_string) : sprintf('%s?%s', $url, $query_string);
	}
	
	/**
	* Ссылка на прямого родственника данной страницы
	*
	* Папка это или страница - необходимо проверять и учитывать
	*/
	public function descendant_link($row)
	{
		static $base_url;
		
		if( !$base_url )
		{
			$ary = pathinfo($this->url);
			$base_url = isset($ary['extension']) ? $ary['dirname'] : $this->url;
		}
		
		$url = ( $row['is_dir'] ) ? $row['page_url'] : (($row['page_url'] != $this->config['router.directory_index']) ? (($this->format) ? sprintf('%s.%s', $row['page_url'], $this->format) : $row['page_url']) : '');
		
		return ilink(sprintf('%s/%s', $base_url, $url));
	}
	
	/**
	* Данные раздела (ветви дерева страниц)
	*/
	public function get_page_branch($page_id, $type = 'all', $order = 'descending', $include_self = true)
	{
		switch( $type )
		{
			case 'parents':  $condition = 'p1.left_id BETWEEN p2.left_id AND p2.right_id'; break;
			case 'children': $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id'; break;
			default:         $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id OR p1.left_id BETWEEN p2.left_id AND p2.right_id';
		}

		$rows = array();

		$sql = '
			SELECT
				p2.*
			FROM
				' . PAGES_TABLE . ' p1
			LEFT JOIN
				' . PAGES_TABLE . ' p2 ON (' . $condition . ')
			WHERE
				p1.site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				p2.site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				p1.page_id = ' . $this->db->check_value($page_id) . '
			ORDER BY
				p2.left_id ' . (($order == 'descending') ? 'ASC' : 'DESC');
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			if( !$include_self && $row['page_id'] == $page_id )
			{
				continue;
			}

			$rows[] = $row;
		}

		$this->db->freeresult();

		return $rows;
	}
	
	/**
	* Прямые родственники страницы (второстепенное меню)
	*/
	public function get_page_descendants($page_id = false)
	{
		if( $page_id === false )
		{
			$page_id = $this->data['is_dir'] ? $this->data['page_id'] : $this->data['parent_id'];
		}
		
		$page_id = (int) $page_id;
		$rows = array();
		
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				parent_id = ' . $page_id . '
			AND
				site_id = ' . $this->data['site_id'] . '
			AND
				page_display > 0
			ORDER BY
				left_id ASC';
		$this->db->query($sql);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	/**
	* Возврат ссылки на обработчик
	*/
	public function get_handler_url($handler, array $params = array())
	{
		if( 0 === strpos($handler, '\\') )
		{
			/**
			* Обращение по абсолютному адресу
			* Чаще всего к модулям движка
			*
			* \engine\modules\gallery::index
			*/
			/* Разработчик знает, что подключает */
			if( isset($this->handlers_urls[$handler]) )
			{
				return $this->get_url_with_params($this->handlers_urls[$handler], $params);
			}
			
			return '/';
		}
		
		/**
		* Обращение к методу текущего модуля
		*/
		if( false === strpos($handler, '::') )
		{
			if( isset($this->urls[$handler]) )
			{
				return $this->get_url_with_params($this->urls[$handler], $params);
			}
			
			return;
		}
		
		/**
		* Обращение по относительному адресу
		*
		* Если обращение исходит из модуля app\modules\csstats\servers::single
		* к maps::index, то сначала будет произведена попытка загрузить
		* app\modules\csstats\maps::index, а затем app\maps::index
		*/
		$class = get_class($this);
		$class = substr($class, 4);
		$diff = substr_count($class, '\\') - substr_count($handler, '\\');
		
		if( $diff > 0 )
		{
			if( false != $prefix = implode('\\', array_slice(explode('\\', $class), 0, $diff)) )
			{
				$full_handler = $prefix . '\\' . $handler;
				
				if( isset($this->handlers_urls[$full_handler]) )
				{
					return $this->get_url_with_params($this->handlers_urls[$full_handler], $params);
				}
			}
		}
		
		if( isset($this->handlers_urls[$handler]) )
		{
			return $this->get_url_with_params($this->handlers_urls[$handler], $params);
		}
		
		return;
	}

	/**
	* Подстановка значений вместо параметров ($n)
	*
	* /проекты/$0/задачи/$1.html => /проекты/www.ru/задачи/важные.html
	*/
	public function get_url_with_params($url, array $params = array())
	{
		if( empty($params) )
		{
			return $url;
		}
		
		$ary = array();
		
		for( $i = 0, $len = sizeof($params); $i < $len; $i++ )
		{
			$ary[] = '$' . $i;
		}
		
		return str_replace($ary, $params, $url);
	}
	
	/**
	* Карта ссылок на методы обработчика
	*/
	public function obtain_handlers_urls()
	{
		$handler = get_class($this);
		$this->handlers_urls = $this->cache->obtain_handlers_urls($this->data['site_id']);
		
		if( 0 === strpos($handler, 'app\\') )
		{
			$handler = substr($handler, 4);
		}
		
		$pos = strlen($handler) + 2;

		foreach( $this->handlers_urls as $method => $url )
		{
			if( 0 === strpos($method, $handler . '::') )
			{
				$this->urls[substr($method, $pos)] = $url;
			}
		}
		
		return $this;
	}

	/**
	* Шапка
	*/
	public function page_header()
	{
		if( defined('HEADER_PRINTED') )
		{
			return $this;
		}
		
		/* Запрет кэширования страниц */
		header('Cache-Control: no-cache, pre-check=0, post-check=0');
		header('Expires: -1');
		header('Pragma: no-cache');

		$copy_year = $this->config['copy_year'] . (($this->config['copy_year'] < date('Y')) ? '-' . date('Y') : '');

		$this->template->assign('copy_year', $copy_year);

		define('HEADER_PRINTED', true);
		return $this;
	}

	/**
	* Нижняя часть страницы
	*/
	public function page_footer()
	{
		if( $this->template->file )
		{
			$this->template->display();
		}
		
		/* Вывод профайлера только для html-документов */
		$display_profiler = $this->format === 'html';

		garbage_collection($display_profiler);
		exit;
	}
	
	/**
	* Установка заголовка Content-type согласно запрашиваемому формату
	*/
	public function set_appropriate_content_type()
	{
		switch( $this->format )
		{
			case 'json': $type = 'application/json'; break;
			case 'xml':  $type = 'text/xml'; break;

			/* Веб-сервер по умолчанию устанавливает text/html */
			default: return $this;
		}
		
		header('Content-type: ' . $type . '; charset=utf-8');
		return $this;
	}

	/**
	* Установка шаблона по умолчанию
	* При ajax-запросах префикс становится ajax/
	*
	* app\news (index) -> news_index.html
	* app\csstats\playerinfo (chat) -> csstats/playerinfo_chat.html
	*/
	public function set_default_template()
	{
		if( !$this->format )
		{
			return $this;
		}
		
		$filename = str_replace('\\', '/', get_class($this));
		
		if( 0 === strpos($filename, 'app/') )
		{
			$filename = substr($filename, 4);
		}
		
		if( 0 === strpos($filename, 'engine/modules/') )
		{
			$filename = substr($filename, 15);
		}
		
		$this->template->file = sprintf('%s_%s.%s', $filename, $this->method, $this->format);
		
		if( $this->request->is_ajax )
		{
			$this->template->file = sprintf('ajax/%s_%s.%s', $filename, $this->method, $this->format);
		}
		
		return $this;
	}
	
	/**
	* Передача данных страницы шаблонизатору
	*/
	public function set_page_data()
	{
		$this->template->assign('page', $this->data);
		
		return $this;
	}

	/**
	* Передача меню сайта шаблонизатору
	*/
	public function set_site_menu()
	{
		$menu     = $this->cache->obtain_menu($this->data['site_id']);
		$page_url = ilink($this->full_url);
		$root_url = ilink();
		
		foreach( $menu as $row )
		{
			if( $row['URL'] == $root_url )
			{
				if( $page_url == $row['URL'] )
				{
					$row['ACTIVE'] = true;
				}
			}
			else
			{
				if( 0 === mb_strpos($page_url, $row['URL']) )
				{
					$row['ACTIVE'] = true;
					
					if( !empty($row['children']) )
					{
						$this->recursive_set_menu_active_items($row['children'], $row['URL']);
					}
				}
			}
			
			$this->template->append('menu', $row);
		}
		
		return $this;
	}

	/**
	* Передача локального меню раздела шаблонизатору
	*/
	public function set_site_submenu()
	{
		$rows = $this->get_page_descendants();
		
		foreach( $rows as $row )
		{
			$this->template->append('submenu', array(
				'ACTIVE' => $this->data['page_id'] == $row['page_id'],
				'IMAGE'  => $row['page_image'],
				'TITLE'  => $row['page_name'],
				
				'U_VIEW' => $this->descendant_link($row)
			));
		}
		
		return $this;
	}
	
	public function smarty_function_url_for($params, $template)
	{
		$handler = !empty($params['handler']) ? $params['handler'] : '';
		$args    = !empty($params['params']) ? $params['params'] : array();
		
		return $this->get_handler_url($handler, $args);
	}
	
	/**
	* Просмотр статичной страницы
	*/
	public function static_page()
	{
		$this->template->file = 'static_page_index.html';
	}

	/**
	* Установка SEO-параметров
	*/
	protected function append_seo_params($row)
	{
		if( isset($row['seo_title']) && $row['seo_title'] )
		{
			$this->data['page_title'] = $row['seo_title'];
		}
		
		if( isset($row['seo_keys']) && $row['seo_keys'] )
		{
			$this->data['page_keywords'] = $row['seo_keys'];
		}
		
		if( isset($row['seo_desc']) && $row['seo_desc'] )
		{
			$this->data['page_description'] = $row['seo_desc'];
		}
		
		$this->set_page_data();
		
		return $this;
	}

	/**
	* Подсветка активных пунктов меню
	*/
	protected function recursive_set_menu_active_items(&$menu, $section_url)
	{
		static $page_url;
		
		if( !$page_url )
		{
			$page_url = ilink($this->full_url);
		}
		
		for( $i = 0, $len = sizeof($menu); $i < $len; $i++ )
		{
			if( $menu[$i]['URL'] == $section_url )
			{
				if( $page_url == $menu[$i]['URL'] )
				{
					$menu[$i]['ACTIVE'] = true;
					return $this;
				}
			}
			else
			{
				if( 0 === mb_strpos($page_url, $menu[$i]['URL']) )
				{
					$menu[$i]['ACTIVE'] = true;

					if( !empty($menu[$i]['children']) )
					{
						$this->recursive_set_menu_active_items($menu[$i]['children'], $menu[$i]['URL']);
					}
					
					return $this;
				}
			}
		}
		
		return $this;
	}
}
