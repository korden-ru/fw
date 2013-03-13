<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw\template;

class smarty
{
	public $file;
	
	protected $dirs;
	protected $env;
	
	function __construct(array $dirs, $cache_dir)
	{
		$this->dirs = $dirs;
		
		$this->env = new \Smarty();
		$this->env->setTemplateDir($dirs);
		$this->env->compile_dir     = $cache_dir;
		$this->env->caching         = false;
		$this->env->compile_check   = true;
		$this->env->debugging       = false;
		$this->env->error_reporting = E_ALL ^ E_NOTICE;
		$this->env->force_compile   = false;
		$this->env->use_sub_dirs    = false;
	}
	
	/**
	* Переменные цикла
	*/
	public function append()
	{
		call_user_func_array([$this->env, 'append'], func_get_args());
		
		return $this;
	}
	
	/**
	* Присвоение значения переменной
	*/
	public function assign()
	{
		call_user_func_array([$this->env, 'assign'], func_get_args());
		
		return $this;
	}
	
	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = '')
	{
		$file = $file ?: $this->file;
		
		if (!$this->is_template_exist($file))
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}
		
		echo $this->env->display($file);
	}
	
	/**
	* Обработка и возврат данных для вывода
	*/
	public function fetch($file = '')
	{
		$file = $file ?: $this->file;
		
		if (!$this->is_template_exist($file))
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}
		
		return $this->env->fetch($file);
	}
	
	/**
	* Алиас $this->fetch()
	*/
	public function render($file = '')
	{
		return $this->fetch($file);
	}
	
	/**
	* Проверка существования шаблона
	*/
	protected function is_template_exist($file)
	{
		foreach ($this->dirs as $dir)
		{
			if (file_exists("{$dir}/{$file}"))
			{
				return true;
			}
		}
		
		return false;
	}
}
