<?php
/**
*
* @package cms.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\template;

define('SMARTY_DIR', $src_root_path . 'lib/smarty/3.1.7/Smarty/');
require(SMARTY_DIR . 'Smarty.class.php');

class smarty extends \Smarty
{
	public $file;
	
	function __construct()
	{
		global $site_root_path;

		parent::__construct();

		$this->template_dir = defined('IN_ACP') ? $site_root_path . 'acp/templates/' : $site_root_path . '../templates/';
		$this->compile_dir  = $site_root_path . '../cache/templates/';

		$this->caching         = false;
		$this->compile_check   = true;
		$this->debugging       = false;
		$this->error_reporting = E_ALL ^ E_NOTICE;
		$this->force_compile   = false;
		$this->use_sub_dirs    = true;
	}
	
	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = '')
	{
		$this->file = ( $file ) ?: $this->file;
		
		return parent::display($this->file);
	}
}
