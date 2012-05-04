<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache;

use engine\helpers\traverse\tree\site_pages;

/**
* Слой для работы с кэшем
*/
class service
{
	public $sql_rowset;
	public $sql_row_pointer;
	
	protected $db;
	protected $driver;
	protected $site_info;

	function __construct($driver, $db)
	{
		$this->db = $db;
		
		$this->set_driver($driver);
	}
	
	public function _set_site_info($site_info)
	{
		$this->site_info = $site_info;
		
		return $this;
	}
	
	/**
	* Возвращает название используемого кэша
	*/
	public function get_driver()
	{
		return $this->driver;
	}
	
	/**
	* Устанавливает новый механизм работы с кэшем
	*/
	public function set_driver($driver)
	{
		$this->driver = $driver;
		
		$this->sql_rowset      =& $this->driver->sql_rowset;
		$this->sql_row_pointer =& $this->driver->sql_row_pointer;
	}

	/**
	* Установка префикса записей
	*/
	public function set_prefix($prefix)
	{
		$this->driver->set_prefix($prefix);
	}
	
	public function sql_save($query, &$query_result, $ttl)
	{
		$this->driver->sql_save($query, $query_result, $ttl);
	}
	
	public function __call($method, $args)
	{
		return call_user_func_array(array($this->driver, $method), $args);
	}

	/**
	* Список динамических страниц
	*/
	public function obtain_handlers_urls($site_id)
	{
		global $app;
		static $cache_entry, $handlers;
		
		if( !$site_id )
		{
			return false;
		}
		
		$cache_entry = sprintf('%s_handlers_%s', $this->site_info['domain'], $this->site_info['language']);
		
		if( empty($handlers) && (false === $handlers = $this->driver->_get($cache_entry)) )
		{
			$sql = '
				SELECT
					*
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				ORDER BY
					left_id ASC';
			$this->db->query($sql);
			$traversal = new traverse_handlers_urls();
			$traversal->_set_config($app['config']);
			
			while( $row = $this->db->fetchrow() )
			{
				$traversal->process_node($row);
			}
			
			$this->db->freeresult();
			$handlers = $traversal->get_tree_data();
			
			$this->driver->_set($cache_entry, $handlers);
		}
		
		return $handlers;
	}

	/**
	* Глобальное меню сайта (page_display = 2)
	*/
	public function obtain_menu($site_id)
	{
		global $app;
		
		if( !$site_id )
		{
			return false;
		}
		
		$cache_entry = sprintf('%s_menu_%s', $this->site_info['domain'], $this->site_info['language']);
		
		if( false === $menu = $this->driver->_get($cache_entry) )
		{
			$sql = '
				SELECT
					*
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				ORDER BY
					left_id ASC';
			$this->db->query($sql);
			$traversal = new traverse_menu(true);
			$traversal->_set_config($app['config']);
			
			while( $row = $this->db->fetchrow() )
			{
				$traversal->process_node($row);
			}
			
			$this->db->freeresult();
			$menu = $traversal->get_tree_data();
			
			$this->driver->_set($cache_entry, $menu);
		}
		
		return $menu;
	}

	/**
	* Список сайтов
	*/
	public function obtain_sites()
	{
		static $sites;
		
		if( empty($sites) && (false === $sites = $this->driver->_get('fw_sites')) )
		{
			$sql = '
				SELECT
					*
				FROM
					' . SITES_TABLE . '
				ORDER BY
					site_url ASC,
					site_language ASC';
			$this->db->query($sql);
			$sites = $this->db->fetchall();
			$this->db->freeresult();
			$this->driver->_set('fw_sites', $sites);
		}

		return $sites;
	}
}

/**
* Дерево ссылок на методы
*/
class traverse_handlers_urls extends site_pages
{
	protected function tree_append($data)
	{
		if( !$this->row['page_handler'] || !$this->row['handler_method'] )
		{
			return false;
		}
		
		/**
		* Замена меток (*) на параметры ($n)
		*
		* /проекты/(*)/задачи/(*).html => /проекты/$0/задачи/$1.html
		*/
		$i = 0;

		while( false !== $pos = strpos($data, '*') )
		{
			$data = substr_replace($data, '$' . $i++, $pos, 1);
		}

		$this->tree[$this->row['page_handler'] . '::' . $this->row['handler_method']] = $data;
	}
}

/**
* Древовидное меню
*/
class traverse_menu extends site_pages
{
	protected function get_data()
	{
		$ary = parent::get_data();
		
		return array(
			'ID'    => $this->row['page_id'],
			'IMAGE' => $this->row['page_image'],
			'TITLE' => $this->row['page_name'],
			'URL'   => $ary['url'],
			'children' => array()
		);
	}
	
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_display'] != 2;
	}
}
