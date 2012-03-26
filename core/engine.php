<?php

namespace engine\core;

class engine
{
	/* Page information (from '.SQL_PREFIX.'pages_table)
	------------------------------------------------*/
	public $page_info;

	/* Navigation string (etc. Main >> Catalog >> Programs >> ThisCMS)
	------------------------------------------------*/
	public $nav = array(0 => array('title' => 'Главная', 'link' => '/'));
	
	/* Required module and URL string (QUERY_STRING from mod_rewrite)
	------------------------------------------------*/
	public $moduleName;
	public $firstParam  = null;
	public $secondParam = null;
	public $thirdParam  = null;
	public $fourthParam = null;

	/* Program version
	------------------------------------------------*/
	public $programVersion = 'Версия CMS: 3.0.1';

	public function __construct()
	{
		/* Set URL params
		-----------------------------------------*/
		if(isset($_GET['page']))
			$this->moduleName = $_GET['page'];

		if(isset($_GET['page1']))
			$this->firstParam = clearModifyUrl($_GET['page1']);

		if(isset($_GET['page2']))
			$this->secondParam = clearModifyUrl($_GET['page2']);

		if(isset($_GET['page3']))
			$this->thirdParam = clearModifyUrl($_GET['page3']);

		if(isset($_GET['page4']))
			$this->fourthParam = clearModifyUrl($_GET['page4']);
	}
	
	/**
	* Получаем данные выбранной страницы
	*/
	function init_page_info($page_url)
	{
		global $db, $template;
		
		$sql = '
			SELECT
				*
			FROM
				tcms_pages
			WHERE
				activation = 1
			AND
				url = ' . $db->check_value($page_url);
		$result = $db->query($sql);
		$this->page_info = $db->fetchrow($result);
		$db->freeresult($result);
		
		if( !$this->page_info )
		{
			gopage('/');
		}
		
		$template->assign(array(
			'PAGE_TITLE'       => $this->page_info['title'],
			'PAGE_PARENT'      => $this->page_info['parent'],
			'PAGE_TEXT'        => $this->page_info['text'],
			'PAGE_NAME'        => ($this->page_info['name'] == '') ? $this->page_info['title'] : $this->page_info['name'],
			'PAGE_KEYWORDS'    => $this->page_info['keywords'],
			'PAGE_DESCRIPTION' => $this->page_info['description'],
			'PAGE_SCRIPT'      => $this->page_info['script'],
			'PAGE_URL'         => $this->page_info['url'],
			'GALLERY_TITLE'    => isset($this->page_info['gallery_title']) ? @$this->page_info['gallery_title'] : 'Галерея',
			'VITRINA_TITLE'    => isset($this->page_info['vitrina_title']) ? @$this->page_info['vitrina_title'] : 'Витрина товаров',
			'PAGE_HEADER'      => isset($this->page_info['header'])?$this->page_info['header']:'page_header.html',
			'PAGE_FOOTER'      => isset($this->page_info['footer'])?$this->page_info['footer']:'page_footer.html',
			'PAGE_TYPE'        => $this->page_info['page_type']
		));
	}
	
	/* Parse navigation row
	------------------------------------------*/
  	public function ParseNavigation()
	{
		global $template;

		if(!isset($this->nav) || empty($this->nav) || !is_array($this->nav))
		{
			exit('Unable to parse navigation bar');
		}
		
		$i = 1;
		$count = count($this->nav);
		foreach($this->nav as $key => $nav)
		{
			$template->append('nav', array(
					'TITLE'		=> $nav['title'],//isset($t)?$t:$nav['title'],
					'LINK'		=> $nav['link'],
					'ZNAK'		=> $i!=$count?'&nbsp;>&nbsp;':null
			));	
		$i++;
		}
	}
}
