<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

use engine\cache\factory as cache_factory;
use engine\config\db as config_db;
use engine\db\mysqli as db_mysqli;
use engine\template\smarty;

/**
* Контейнер приложения
*/
class application implements \ArrayAccess
{
	const VERSION = '3.5-dev';
	
	private $values;
	
	function __construct(array $values = array())
	{
		$this->values = $values;
		
		$app = $this;
		
		/* Автозагрузчик классов */
		$this['autoloader'] = $this->share(function() use ($app) {
			$loader = new autoloader($app['acm.prefix']);
			$loader->register();
			
			return $loader;
		});
		
		$this['template'] = $this->share(function() {
			return new smarty();
		});
		
		$this['profiler'] = $this->share(function() use ($app) {
			return new profiler($app['template']);
		});

		/* Данные запроса */
		$this['request'] = $this->share(function() {
			return new request();
		});
		
		/* Инициализация кэша */
		$this['cache'] = $this->share(function() use ($app) {
			$factory = new cache_factory($app['acm.type'], $app['acm.prefix']);
			return $factory->get_service();
		});

		/* Подключение к базе данных */
		$this['db'] = $this->share(function() use ($app) {
			$db = new db_mysqli($app['db.host'], $app['db.user'], $app['db.pass'], $app['db.name'], $app['db.port'], $app['db.sock'], $app['db.pers']);
			$db->_set_cache($app['cache'])
				->_set_profiler($app['profiler']);
			
			return $db;
		});
		
		$this['config'] = $this->share(function() use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info'], CONFIG_TABLE);
		});

		$this['user'] = $this->share(function() use ($app) {
			return new user($app['cache'], $app['config'], $app['db'], $app['request']);
		});
		
		$this['router'] = $this->share(function() use ($app) {
			return new router($app['cache'], $app['config'], $app['db'], $app['request'], $app['template'], $app['user']);
		});
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, \Closure $callable)
	{
		if( !array_key_exists($id, $this->values) )
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$factory = $this->values[$id];
		
		if( !($factory instanceof \Closure) )
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
		if( !array_key_exists($id, $this->values) )
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
			
			if( is_null($object) )
			{
				$object = $callable($c);
			}
			
			return $object;
		};
	}
	
	public function offsetExists($id)
	{
		return isset($this->values[$id]);
	}
	
	public function offsetGet($id)
	{
		if( !array_key_exists($id, $this->values) )
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		return $this->values[$id] instanceof \Closure ? $this->values[$id]($this) : $this->values[$id];
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
