<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw\core;

/**
* Маршрутизатор запросов
*/
class _router extends router
{
	/**
	* Данные страницы
	*/
	protected function get_page_row_by_url($page_url, $is_dir = 1, $parent_id = 0)
	{
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				parent_id = ' . $this->db->check_value($parent_id) . '
			AND
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				' . $this->db->in_set('page_url', [$page_url, '*']) . '
			AND
				is_dir = ' . $this->db->check_value($is_dir) . '
			AND
				page_enabled = 1
			ORDER BY
				LENGTH(page_url) DESC';
		$this->db->query_limit($sql, 1);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		/* Загрузка блока */
		if (!$row && !$is_dir && $parent_id)
		{
			$row = get_page_block($page_url, $parent_id, 'pages');
		}
		
		return $row;
	}
}
