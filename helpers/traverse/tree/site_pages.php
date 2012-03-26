<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\helpers\traverse\tree;

use engine\helpers\traverse\tree;

/**
* Обход страниц сайта
*/
class site_pages extends tree
{
	protected $base_url = array();

	public function get_pages_data($pages)
	{
		$this->process_nodes($pages);
		
		return $this->tree;
	}

	/**
	* Ссылка на страницу
	*/
	protected function get_data()
	{
		global $config;
		
		$this->base_url[] = ( $this->row['is_dir'] ) ? $this->row['page_url'] : (($this->row['page_url'] == $config['router_directory_index']) ? '' : (($config['router_default_extension']) ? sprintf('%s.%s', $this->row['page_url'], $config['router_default_extension']) : $this->row['page_url']));
		
		return ( $this->return_as_tree ) ? array('url' => ilink(implode('/', $this->base_url)), 'children' => array()) : ilink(implode('/', $this->base_url));
	}
	
	/**
	* Возврат на уровень вверх
	*/
	protected function on_depth_decrease()
	{
		array_pop($this->base_url);
	}
	
	/**
	* Массив только включенных страниц
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'];
	}
}
