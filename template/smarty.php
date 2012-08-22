<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\template;

define('SMARTY_DIR', FW_DIR . '../lib/smarty/3.1.11/Smarty/');
require(SMARTY_DIR . 'Smarty.class.php');

class smarty extends \Smarty
{
	public $file;
	
	function __construct()
	{
		parent::__construct();
		
		if( defined('IN_ACP') )
		{
			$this->setTemplateDir(array(
				'acp'    => SITE_DIR . 'acp/templates/',
				'engine' => FW_DIR . 'templates',
			));
		}
		else
		{
			$this->setTemplateDir(array(
				'app_shared' => SITE_DIR . '../templates',
				'engine'     => FW_DIR . 'templates',
			));
		}
		
		$this->compile_dir  = SITE_DIR . '../cache/templates/';

		$this->caching         = false;
		$this->compile_check   = true;
		$this->debugging       = false;
		$this->error_reporting = E_ALL ^ E_NOTICE;
		$this->force_compile   = false;
		$this->use_sub_dirs    = false;
	}
	
	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = null, $cache_id = null, $compile_id = null, $parent = null)
	{
		$this->file = $file ?: $this->file;
		
		return parent::display($this->file, $cache_id, $compile_id, $parent);
	}
}
