<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\modules;

use app\models\page;
use engine\helpers\traverse\tree\site_pages;

/**
* Карта сайта
*/
class sitemap extends page
{
	/**
	* Построение карты в требуемом формате
	*/
	public function index()
	{
		$sql = '
			SELECT
				page_id,
				site_id,
				parent_id,
				left_id,
				right_id,
				is_dir,
				page_enabled,
				page_name,
				page_url,
				page_noindex,
				page_image
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			ORDER BY
				left_id ASC';
		$this->db->query($sql);
	}

	/**
	* В формате HTML
	*/
	public function index_html()
	{
		$traversal = new traverse_sitemap_pages_html(true);
		
		while( $row = $this->db->fetchrow() )
		{
			$traversal->process_node($row);
		}
		
		$this->db->freeresult();
		
		$this->template->assign(array(
			'pages' => $traversal->get_tree_data()
		));
	}
	
	/**
	* В формате XML
	*/
	public function index_xml()
	{
		$traversal = new traverse_sitemap_pages_xml();
		
		while( $row = $this->db->fetchrow() )
		{
			$traversal->process_node($row);
		}
		
		$this->db->freeresult();
		
		$this->template->assign(array(
			'pages' => $traversal->get_tree_data(),
			
			'DOMAIN' => $this->user->domain
		));
	}
}

/**
* Обход дерева страниц для построения карты сайта
*/
class traverse_sitemap_pages_html extends site_pages
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

	/**
	* В карту сайта попадают только включенные и индексируемые страницы
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_noindex'] || $this->row['page_url'] == '*';
	}
}

/**
* Обход дерева страниц для построения карты сайта
*/
class traverse_sitemap_pages_xml extends site_pages
{
	protected function get_data()
	{
		global $config;
		
		/**
		* Пропуск индексных страниц, чтобы одна и та же страница
		* не отображалась в карте сайта дважды, например:
		*
		* /новости/ и /новости/index.html
		*
		* Исключение: главная страница сайта
		*/
		if( !$this->row['is_dir'] && $this->row['page_url'] == $config['router_directory_index'] )
		{
			$this->base_url[] = '';
			
			if( !$this->row['parent_id'] )
			{
				return ilink('');
			}
			
			return false;
		}
		
		/* Карта сайта не может содержать кириллические символы */
		$this->row['page_url'] = urlencode($this->row['page_url']);
		
		return parent::get_data();
	}
	
	/**
	* В карту сайта попадают только включенные и индексируемые страницы
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_noindex'] || $this->row['page_url'] == '*';
	}
}
