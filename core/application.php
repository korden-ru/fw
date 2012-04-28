<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

/**
* Контейнер приложения
*/
class application implements ArrayAccess
{
	private $values;
	
	public function __construct(array $values = array())
	{
		$this->values = $values;
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, Closure $callable)
	{
		if( !array_key_exists($id, $this->values) )
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$factory = $this->values[$id];
		
		if( !($factory instanceof Closure) )
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
	public function protect(Closure $callable)
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
	public function share(Closure $callable)
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
		
		return $this->values[$id] instanceof Closure ? $this->values[$id]($this) : $this->values[$id];
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
