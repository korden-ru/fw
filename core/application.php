<?php
/**
* @package korden.fw
* @copyright (c) 2013
*/

namespace fw\core;

use fw\captcha\service as captcha_service;
use fw\captcha\validator as captcha_validator;
use fw\cron\manager as cron_manager;
use fw\config\db as config_db;
use fw\db\mysqli as db_mysqli;
use fw\db\sphinx as db_sphinx;
use fw\session\user;
use fw\template\smarty;
use fw\traits\constants;

/**
* Контейнер приложения
*/
class application implements \ArrayAccess
{
	use constants;
	
	const VERSION = 'master';
	
	private $values;
	
	function __construct(array $values = array())
	{
		$this->values = $values;
		
		$app = $this;
		
		$this['profiler'] = $this->share(function() {
			return new profiler(START_TIME);
		});
		
		$this['autoloader'] = $this->share(function() use ($app) {
			return (new autoloader($app['acm.prefix']))->register();
		});
		
		$this['template'] = $this->share(function() use ($app) {
			define('SMARTY_DIR', "{$app['dir.lib']}/smarty/{$app['version.smarty']}/Smarty/");
			require(SMARTY_DIR . 'Smarty.class.php');

			return new smarty([$app['dir.templates.app'], $app['dir.templates.fw']], $app['dir.templates.cache']);
		});
		
		$this['request'] = $this->share(function() use ($app) {
			return new request($app['request.local_redirect.from'], $app['request.local_redirect.to']);
		});
		
		$this['db'] = $this->share(function() use ($app) {
			return new db_mysqli($app['db.host'], $app['db.user'], $app['db.pass'], $app['db.name'], $app['db.port'], $app['db.sock'], $app['db.pers']);
		});
		
		$this['cache'] = $this->share(function() use ($app) {
			$class = "\\fw\\cache\\driver\\{$app['acm.type']}";
			
			if (file_exists("{$app['dir.app']}/cache/service.php"))
			{
				return new \app\cache\service($app['db'], new $class($app['db'], $app['acm.prefix'], $app['acm.shared_prefix']));
			}
			
			return new \fw\cache\service($app['db'], new $class($app['db'], $app['acm.prefix'], $app['acm.shared_prefix']));
		});

		$this['user'] = $this->share(function() use ($app) {
			return (new user($app['cache'], $app['config'], $app['db'], $app['request'], $app['session.config'], $app['site_info']['id'], $app['urls']['signin']))
				->setup();
		});
		
		$this['auth'] = $this->share(function() use ($app) {
			return (new auth($app['cache'], $app['db'], $app['user']))
				->init($app['user']->data);
		});

		/* Настройки сайта и движка */
		$this['config'] = $this->share(function() use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info'], CONFIG_TABLE);
		});

		$this['router'] = $this->share(function() use ($app) {
			return (new _router())
				->_set_app($app);
		});

		/* Информация об обслуживаемом сайте */
		$this['site_info'] = $this->share(function() use ($app) {
			if (false === $site_info = $app['cache']->get_site_info_by_url($app['request']->hostname, $app['request']->url))
			{
				trigger_error('Сайт не найден', E_USER_ERROR);
			}
			
			$app['request']->set_language($site_info['language'])
				->set_server_name($site_info['domain']);
			
			setlocale(LC_ALL, $site_info['locale']);
			
			return $site_info;
		});

		$this['captcha'] = $this->share(function() use ($app) {
			$class = "\\fw\\captcha\\driver\\{$app['captcha.type']}";

			return new captcha_service($app['config'], $app['db'], $app['request'], $app['user'], new $class($app['dir.fonts'], $app['captcha.fonts']));
		});
		
		$this['captcha_validator'] = $this->share(function() use ($app) {
			return new captcha_validator($app['config'], $app['db'], $app['request'], $app['user']);
		});
		
		$this['cron'] = $this->share(function() use ($app) {
			return (new cron_manager($app['dir.logs'], $app['file.cron.allowed'], $app['file.cron.running']))
				->_set_app($app);
		});
		
		$this['mailer'] = $this->share(function() use ($app) {
			require("{$app['dir.lib']}/swiftmailer/{$app['version.swift']}/swift_init.php");

			return new mailer($app['config'], $app['template']);
		});
		
		$this['sphinx'] = $this->share(function() use ($app) {
			return (new db_sphinx($app['sphinx.host'], '', '', '', $app['sphinx.port'], $app['sphinx.sock']))
				->_set_cache($app['cache'])
				->_set_profiler($app['profiler']);
		});
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, \Closure $callable)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$factory = $this->values[$id];
		
		if (!($factory instanceof \Closure))
		{
			trigger_error(sprintf('Ключ "%s" не содержит объект.', $id));
		}
		
		return $this->values[$id] = function ($c) use ($callable, $factory)
		{
			return $callable($factory($c), $c);
		};
	}

	/**
	* Данный объект не будет вызван при обращении
	* Его необходимо вызывать вручную
	*/
	public function protect(\Closure $callable)
	{
		return function ($c) use ($callable)
		{
			return $callable;
		};
	}
	
	/**
	* Извлечение параметра или определения объекта
	*/
	public function raw($id)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		return $this->values[$id];
	}

	/**
	* Объект-одиночка
	*/
	public function share(\Closure $callable)
	{
		return function ($c) use ($callable)
		{
			static $object;
			
			if (null === $object)
			{
				$object = $callable($c);
			}
			
			return $object;
		};
	}
	
	public function keys()
	{
		return array_keys($this->values);
	}
	
	public function offsetExists($id)
	{
		return array_key_exists($id, $this->values);
	}
	
	public function offsetGet($id)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$is_factory = is_object($this->values[$id]) && method_exists($this->values[$id], '__invoke');
		
		return $is_factory ? $this->values[$id]($this) : $this->values[$id];
	}
	
	public function offsetSet($id, $value)
	{
		$this->values[$id] = $value;
	}
	
	public function offsetUnset($id)
	{
		unset($this->values[$id]);
	}
}
