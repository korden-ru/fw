<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw\models;

use fw\models\page;

/**
* Страница сайта
*/
class _page extends page
{
	/**
	* Установка SEO-параметров
	*/
	protected function append_seo_params($row)
	{
		$this->data['page_title'] = !empty($row['seo_title']) ? $row['seo_title'] : $this->data['page_title'];
		$this->data['page_keywords'] = !empty($row['seo_keys']) ? $row['seo_keys'] : $this->data['page_keywords'];
		$this->data['page_description'] = !empty($row['seo_desc']) ? $row['seo_desc'] : $this->data['page_description'];
		$this->set_page_data();
		
		return $this;
	}
}
