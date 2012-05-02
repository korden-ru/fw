<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache\driver;

/**
* Хранение кэша в памяти
*/
class memory
{
	public $sql_rowset = array();
	public $sql_row_pointer = array();

	protected $prefix;
	
	private $data = array();
	private $is_modified = false;

	private $db;
	
	function __construct($prefix = '', $db)
	{
		$this->db = $db;
		
		$this->set_prefix($prefix);
		
		if( !isset($this->extension) || !extension_loaded($this->extension) )
		{
			trigger_error(sprintf('Не удается найти расширение [%s] для ACM.', $this->extension), E_USER_ERROR);
		}
	}
	
	/**
	* Удаление записи из кэша
	*/
	public function clean($var)
	{
		$this->delete($this->prefix . $var);
	}

	/**
	* Удаление записи из кэша
	*/
	public function delete($var, $table = '')
	{
		if( $var == 'sql' && !empty($table) )
		{
			if( !is_array($table) )
			{
				$table = array($table);
			}
			
			foreach( $table as $table_name )
			{
				if( false === $temp = $this->_get($this->prefix . 'sql_' . $table_name) )
				{
					continue;
				}
				
				foreach( $temp as $md5 => $void )
				{
					$this->_delete($this->prefix . 'sql_' . $md5);
				}
				
				$this->_delete($this->prefix . 'sql_' . $table_name);
			}
			
			return;
		}

		if( !$this->_exists($var) )
		{
			return;
		}

		if( isset($this->data[$var]) )
		{
			$this->is_modified = true;
			unset($this->data[$var]);

			/* cache hit */
			$this->save();
		}
		elseif( $var[0] != '_' )
		{
			$this->_delete($this->prefix . $var);
		}
	}

	/**
	* Получение данных из кэша
	*/
	public function get($var)
	{
		if( !$this->_exists($var) )
		{
			return false;
		}

		if( $var[0] == '_' )
		{
			return $this->data[$var];
		}
		
		return $this->_get($this->prefix . $var);
	}

	/**
	* Загрузка глобальных настроек
	*/
	private function load()
	{
		$this->data = $this->_get($this->prefix . 'global');

		return false !== $this->data;
	}

	/**
	* Сброс кэша
	*/
	protected function purge()
	{
		unset($this->data);
		unset($this->sql_rowset);
		unset($this->sql_row_pointer);

		$this->data = array();
		$this->sql_rowset = array();
		$this->sql_row_pointer = array();
		
		$this->is_modified = false;
	}
	
	/**
	* Запись данных в кэш
	*/
	public function set($var, $data, $ttl = 2592000)
	{
		if( $var[0] == '_' )
		{
			$this->data[$var] = $data;
			$this->is_modified = true;
		}
		else
		{
			$this->_set($this->prefix . $var, $data, $ttl);
		}
	}

	/**
	* Установка префикса записей
	*/
	public function set_prefix($prefix = '')
	{
		$this->prefix = ( $prefix ) ? $prefix . '_' : '';
	}

	/**
	* Существует ли искомая запись в кэше
	*/
	public function sql_exists($query_id)
	{
		return isset($this->sql_rowset[$query_id]);
	}
	
	/**
	* Извлекаем весь результат
	*/
	public function sql_fetchall($query_id)
	{
		if( $this->sql_row_pointer[$query_id] < sizeof($this->sql_rowset[$query_id]) )
		{
			return $this->sql_rowset[$query_id];
		}
		
		return false;
	}
	
	/**
	* Извлекаем поле из текущей строки кэшированного результата sql-запроса
	*/
	public function sql_fetchfield($query_id, $field)
	{
		if( $this->sql_row_pointer[$query_id] < sizeof($this->sql_rowset[$query_id]) )
		{
			return isset($this->sql_rowset[$query_id][$this->sql_row_pointer[$query_id]][$field]) ? $this->sql_rowset[$query_id][$this->sql_row_pointer[$query_id]++][$field] : false;
		}

		return false;
	}
	
	/**
	* Извлекаем запись из кэша
	*/
	public function sql_fetchrow($query_id)
	{
		if( $this->sql_row_pointer[$query_id] < sizeof($this->sql_rowset[$query_id]) )
		{
			return $this->sql_rowset[$query_id][$this->sql_row_pointer[$query_id]++];
		}
		
		return false;
	}
	
	/**
	* Освобождение результата запроса, удаление закэшированных результатов
	*/
	public function sql_freeresult($query_id)
	{
		if( !isset($this->sql_rowset[$query_id]) )
		{
			return false;
		}
		
		unset($this->sql_rowset[$query_id]);
		unset($this->sql_row_pointer[$query_id]);
		
		return true;
	}
	
	/**
	* Загрузка закэшированных результатов sql-запроса
	*/
	public function sql_load($query)
	{
		$query    = preg_replace('#[\n\r\s\t]+#', ' ', $query);
		$query_id = sizeof($this->sql_rowset);
		
		if( false === $result = $this->_get($this->prefix . 'sql_' . md5($query)) )
		{
			return false;
		}
		
		$this->sql_rowset[$query_id] = $result;
		$this->sql_row_pointer[$query_id] = 0;
		
		return $query_id;
	}
	
	/**
	* Перемещение к определенной строке результата
	*/
	public function sql_rowseek($rownum, $query_id)
	{
		if( $rownum >= sizeof($this->sql_rowset[$query_id]) )
		{
			return false;
		}
		
		$this->sql_row_pointer[$query_id] = $rownum;
		
		return true;
	}
	
	/**
	* Сохранение результатов sql-запроса
	*/
	public function sql_save($query, &$query_result, $ttl)
	{
		$query = preg_replace('#[\n\r\s\t]+#', ' ', $query);
		$hash  = md5($query);
		
		/**
		* Какие таблицы затрагивает запрос
		*/
		if( !preg_match('/FROM \\(?(`?\\w+`?(?: \\w+)?(?:, ?`?\\w+`?(?: \\w+)?)*)\\)?/', $query, $regs) )
		{
			return;
		}
		
		$tables = array_map('trim', explode(',', $regs[1]));
		
		foreach( $tables as $table_name )
		{
			/* Опускаем кавычки */
			$table_name = ( $table_name[0] == '`' ) ? substr($table_name, 1, -1) : $table_name;
			
			if( false !== $pos = strpos($table_name, ' ') )
			{
				$table_name = substr($table_name, 0, $pos);
			}
			
			if( false === $temp = $this->_get($this->prefix . 'sql_' . $table_name) )
			{
				$temp = array();
			}
			
			$temp[$hash] = true;
			
			$this->_set($this->prefix . 'sql_' . $table_name, $temp);
		}
		
		$query_id = sizeof($this->sql_rowset);
		$this->sql_rowset[$query_id] = array();
		$this->sql_row_pointer[$query_id] = 0;
		
		while( $row = $this->db->fetchrow($query_result) )
		{
			$this->sql_rowset[$query_id][] = $row;
		}
		
		$this->db->freeresult($query_result);
		
		$this->_set($this->prefix . 'sql_' . $hash, $this->sql_rowset[$query_id], $ttl);
		
		$query_result = $query_id;
	}

	/**
	* Выгрузка данных
	*/
	protected function unload()
	{
		$this->save();
		unset($this->data);
		unset($this->sql_rowset);
		unset($this->sql_row_pointer);

		$this->data = array();
		$this->sql_rowset = array();
		$this->sql_row_pointer = array();
	}

	/**
	* Проверка наличия данных в кэше
	*/
	private function _exists($var)
	{
		if( $var[0] == '_' )
		{
			if( !sizeof($this->data) )
			{
				$this->load();
			}

			return isset($this->data[$var]);
		}
		else
		{
			return true;
		}
	}
	
	/**
	* Сохранение глобальных настроек
	*/
	private function save()
	{
		if( !$this->is_modified )
		{
			return;
		}

		$this->_set($this->prefix . 'global', $this->data, 2592000);
		$this->is_modified = false;
	}
}
