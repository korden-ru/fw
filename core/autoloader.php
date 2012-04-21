<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

/**
* Автозагрузчик классов
*/
class autoloader
{
	private $apc_prefix;
	private $namespaces = array();
	private $namespace_fallbacks = array();
	private $prefixes = array();
	private $prefix_fallbacks = array();
	private $use_include_path = false;
	
	public function __construct($prefix = false)
	{
		if( $prefix && extension_loaded('apc') )
		{
			$this->apc_prefix = $prefix . '_';
		}
	}
	
	/**
	* Загрузка заданного класса
	*/
	public function autoload($class)
	{
		if( $this->apc_prefix && false !== $file = apc_fetch($this->apc_prefix . $class) )
		{
			require $file;
			return;
		}
		
		if( $file = $this->find_file($class) )
		{
			if( $this->apc_prefix )
			{
				apc_store($this->apc_prefix . $class, $file);
			}
			
			require $file;
		}
	}
	
	/**
	* Поиск файла, в котором находится искомый класс
	*/
	public function find_file($class)
	{
		if( '\\' == $class[0] )
		{
			$class = substr($class, 1);
		}
		
		if( false !== $pos = strrpos($class, '\\') )
		{
			/* Пространства имен */
			$namespace  = substr($class, 0, $pos);
			$class_name = substr($class, $pos + 1);

			if( false !== strpos($namespace, '\\') )
			{
				list(, $suffix) = explode('\\', $namespace, 2);
			}
			else
			{
				$suffix = '';
			}
			
			$filename = str_replace('\\', DIRECTORY_SEPARATOR, $suffix) . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
			
			foreach( $this->namespaces as $ns => $dirs )
			{
				/* NS-именованные классы */
				if( 0 !== strpos($namespace, $ns) )
				{
					continue;
				}
				
				foreach( $dirs as $dir )
				{
					$file = $dir . DIRECTORY_SEPARATOR . $filename;
					
					if( is_file($file) )
					{
						return $file;
					}
				}
			}
			
			foreach( $this->namespace_fallbacks as $dir )
			{
				$file = $dir . DIRECTORY_SEPARATOR . $filename;
				
				if( is_file($file) )
				{
					return $file;
				}
			}
		}
		else
		{
			/* PEAR-именованные классы */
			$filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
			
			foreach( $this->prefixes as $prefix => $dirs )
			{
				if( 0 !== strpos($class, $prefix) )
				{
					continue;
				}
				
				foreach( $dirs as $dir )
				{
					$file = $dir . DIRECTORY_SEPARATOR . $filename;
					
					if( is_file($file) )
					{
						return $file;
					}
				}
			}
			
			foreach( $this->prefix_fallbacks as $dir )
			{
				$file = $dir . DIRECTORY_SEPARATOR . $filename;
				
				if( is_file($file) )
				{
					return $file;
				}
			}
		}
		
		if( $this->use_include_path && $file = stream_resolve_include_path($filename) )
		{
			return $file;
		}
	}
	
	/**
	* Регистрация загрузчика
	*/
	public function register($prepend = false)
	{
		spl_autoload_register(array($this, 'autoload'), true, $prepend);
	}
	
	/**
	* Регистрация директорий для поиска класса в определенном пространстве имен
	*/
	public function register_namespace($namespace, $dirs)
	{
		$this->namespaces[$namespace] = (array) $dirs;
	}
	
	/**
	* Регистрация резервной директории для поиска в ней NS-именованных классов
	*/
	public function register_namespace_fallback($dir)
	{
		$this->namespace_fallbacks[] = $dir;
	}
	
	/**
	* Регистрация резервных директорий для поиска в них NS-именованных классов
	*/
	public function register_namespace_fallbacks(array $dirs)
	{
		$this->namespace_fallbacks = $dirs;
	}
	
	/**
	* Регистрация директорий для поиска классов в определенном пространстве имен
	*/
	public function register_namespaces(array $ary)
	{
		foreach( $ary as $namespace => $dirs )
		{
			$this->namespaces[$namespace] = (array) $dirs;
		}
	}
	
	/**
	* Регистрация директорий для поиска класса с определенным префиксом
	*/
	public function register_prefix($prefix, $dirs)
	{
		$this->prefixes[$prefix] = (array) $dirs;
	}
	
	/**
	* Регистрация резервной директории для поиска в ней PEAR-именованных классов
	*/
	public function register_prefix_fallback($dir)
	{
		$this->prefix_fallbacks[] = $dir;
	}
	
	/**
	* Регистрация резервных директорий для поиска в них PEAR-именованных классов
	*/
	public function register_prefix_fallbacks(array $dirs)
	{
		$this->prefix_fallbacks = $dirs;
	}
	
	/**
	* Регистрация директорий для поиска классов с определенным префиксом
	*/
	public function register_prefixes(array $ary)
	{
		foreach( $ary as $prefix => $dirs )
		{
			$this->prefixes[$prefix] = (array) $dirs;
		}
	}
	
	/**
	* Следует ли искать классы в папке по умолчанию (include_path)
	*/
	public function use_include_path($flag)
	{
		$this->use_include_path = $flag;
	}
}
