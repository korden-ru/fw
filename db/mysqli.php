<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\db;

/**
* Класс работы с MySQL версии 4.1 и выше
*/
class mysqli
{
	/**
	* Глобальные переменные класса
	*/
	private $connect_id;
	private $query_result;
	private $num_queries = 0;
	private $transaction = false;
	private $transactions = 0;

	private $server;
	private $user;
	private $password;
	private $database;
	private $port;
	private $socket;
	
	/**
	* Сбор параметров
	* Само подключение к серверу выполняется при первом запросе
	*/
	function __construct($dbhost, $dbuser, $dbpass, $dbname, $dbport = false, $dbsock = '', $persistent = false)
	{
		$this->server = $dbhost;
		$this->user = $dbuser;
		$this->password = $dbpass;
		$this->database = $dbname;
		$this->port = ( !$dbport ) ? null : $dbport;
		$this->socket = $dbsock;
		
		if( $persistent !== false && version_compare(PHP_VERSION, '5.3.0', '>=') )
		{
			$this->server = 'p:' . $this->server;
		}
		
		$this->num_queries = array(
			'cached' => 0,
			'normal' => 0,
			'total'  => 0
		);
	}
	
	/**
	* Увеличение счетчика запросов
	*/
	private function add_num_queries($cached = false)
	{
		$this->num_queries['cached'] += ( $cached !== false ) ? 1 : 0;
		$this->num_queries['normal'] += ( $cached !== false ) ? 0 : 1;
		$this->num_queries['total']++;
	}

	/**
	* Затронутые поля
	*/
	public function affected_rows()
	{
		return ( $this->connect_id ) ? mysqli_affected_rows($this->connect_id) : false;
	}

	/**
	* Преобразование массива в строку
	* и выполнение запроса
	*/
	public function build_array($query, $data = false)
	{
		if( !is_array($data) )
		{
			return false;
		}

		$fields = $values = array();

		if( $query == 'INSERT' )
		{
			foreach( $data as $key => $value )
			{
				$fields[] = $key;
				$values[] = $this->check_value($value);
			}

			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		elseif( $query == 'SELECT' || $query == 'UPDATE' )
		{
			$values = array();
			
			foreach( $data as $key => $value )
			{
				$values[] = $key . ' = ' . $this->check_value($value);
			}

			$query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
		}

		return $query;
	}
	
	/**
	* Построение sql-запроса из массива данных
	*/
	public function build_query($query, $array)
	{
		$sql = '';
		
		switch( $query )
		{
			case 'SELECT':
			case 'SELECT_DISTINCT':
			
				$sql = str_replace('_', '', $query) . ' ' . ((is_array($array['SELECT'])) ? implode(', ', $array['SELECT']) : $array['SELECT']) . ' FROM ';
				
				if( is_array($array['FROM']) )
				{
					$table_array = $aliases = array();
					$used_multi_alias = false;

					foreach( $array['FROM'] as $table_name => $alias )
					{
						if( is_array($alias) )
						{
							$used_multi_alias = true;

							foreach( $alias as $multi_alias )
							{
								$table_array[] = $table_name . ' ' . $multi_alias;
								$aliases[] = $multi_alias;
							}
						}
						else
						{
							$table_array[] = $table_name . ' ' . $alias;
							$aliases[] = $alias;
						}
					}

					$sql .= implode(', ', $table_array);
				}
				else
				{
					$sql .= $array['FROM'];
				}
				
				if( !empty($array['LEFT_JOIN']) )
				{
					if( is_array($array['LEFT_JOIN']) )
					{
						foreach( $array['LEFT_JOIN'] as $join )
						{
							$sql .= ' LEFT JOIN ' . key($join['FROM']) . ' ' . current($join['FROM']) . ' ON (' . $join['ON'] . ')';
						}
					}
					else
					{
						$sql .= ' LEFT JOIN ' . $array['LEFT_JOIN'];
					}
				}
				
				if( !empty($array['WHERE']) )
				{
					$sql .= ' WHERE ' . implode(' AND ', $array['WHERE']);
				}
				
				if( !empty($array['GROUP_BY']) )
				{
					$sql .= ' GROUP BY ' . $array['GROUP_BY'];
				}
				
				if( !empty($array['ORDER_BY']) )
				{
					$sql .= ' ORDER BY ' . $array['ORDER_BY'];
				}
				
			break;
		}
		
		return $sql;
	}

	/**
	* Сверяем тим переменной и её значение,
	* строки также экранируем
	*/
	public function check_value($value)
	{
		if( is_null($value) )
		{
			return 'NULL';
		}
		elseif( is_string($value) )
		{
			return "'" . $this->escape($value) . "'";
		}
		else
		{
			return ( is_bool($value) ) ? intval($value) : $value;
		}
	}

	/**
	* Закрытие текущего подключения
	*/
	public function close()
	{
		if( !$this->connect_id )
		{
			return false;
		}

		if( $this->transaction )
		{
			do
			{
				$this->transaction('commit');
			}
			while( $this->transaction );
		}

		return mysqli_close($this->connect_id);
	}

	/**
	* Экранируем символы
	*/
	public function escape($message)
	{
		if( !$this->connect_id )
		{
			$this->connect();
		}
		
		return mysqli_real_escape_string($this->connect_id, $message);
	}
	
	/**
	* Заносим полученные данные в цифровой массив
	*
	* @param string $field Поле, по которому создавать массив
	*/
	public function fetchall($query_id = false, $field = false)
	{
		global $cache;

		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( $query_id !== false )
		{
			$result = array();

			if( !is_object($query_id) && isset($cache->sql_rowset[$query_id]) )
			{
				if( $field !== false )
				{
					$ary = $cache->sql_fetchall($query_id);
					
					foreach( $ary as $row )
					{
						$result[$row[$field]] = $row;
					}
					
					return $result;
				}
				
				return $cache->sql_fetchall($query_id);
			}
			
			while( $row = $this->fetchrow($query_id) )
			{
				if( $field !== false )
				{
					$result[$row[$field]] = $row;
				}
				else
				{
					$result[] = $row;
				}
			}

			return $result;
		}

		return false;
	}
	
	/**
	* Извлечение поля
	* Если rownum = false, то используется текущая строка (по умолчанию: 0)
	*/
	public function fetchfield($field, $rownum = false, $query_id = false)
	{
		global $cache;

		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( $query_id !== false )
		{
			if( $rownum !== false )
			{
				$this->rowseek($rownum, $query_id);
			}

			if( !is_object($query_id) && isset($cache->sql_rowset[$query_id]) )
			{
				return $cache->sql_fetchfield($query_id, $field);
			}

			$row = $this->fetchrow($query_id);
			
			return isset($row[$field]) ? $row[$field] : false;
		}

		return false;
	}

	/**
	* Выборка
	*/
	public function fetchrow($query_id = false)
	{
		global $cache;

		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( !is_object($query_id) && isset($cache->sql_rowset[$query_id]) )
		{
			return $cache->sql_fetchrow($query_id);
		}
		
		if( $query_id !== false )
		{
			$result = mysqli_fetch_assoc($query_id);
			return $result !== null ? $result : false;
		}
		
		return false;
	}

	/**
	* Освобождение памяти
	*/
	public function freeresult($query_id = false)
	{
		global $cache;
		
		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( !is_object($query_id) && isset($cache->sql_rowset[$query_id]) )
		{
			return $cache->sql_freeresult($query_id);
		}
		
		return mysqli_free_result($query_id);
	}

	/**
	* Ряд значений
	*/
	public function in_set($field, $array, $negate = false, $allow_empty_set = false)
	{
		if( !sizeof($array) )
		{
			if( !$allow_empty_set )
			{
				// Print the backtrace to help identifying the location of the problematic code
				$this->error('No values specified for SQL IN comparison');
			}
			else
			{
				// NOT IN () actually means everything so use a tautology
				if( $negate )
				{
					return '1=1';
				}
				// IN () actually means nothing so use a contradiction
				else
				{
					return '1=0';
				}
			}
		}

		if( !is_array($array) )
		{
			$array = array($array);
		}

		if( sizeof($array) == 1 )
		{
			@reset($array);
			$var = current($array);

			return $field . ($negate ? ' <> ' : ' = ') . $this->check_value($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map(array($this, 'check_value'), $array)) . ')';
		}
	}

	/**
	* ID последнего добавленного элемента
	*/
	public function insert_id()
	{
		return ( $this->connect_id ) ? mysqli_insert_id($this->connect_id) : false;
	}

	/**
	* Экранирование LIKE запроса
	*/
	public function like_expression($expression)
	{
		$expression = str_replace(array('_', '%'), array("\_", "\%"), $expression);
		$expression = str_replace(array(chr(0) . "\_", chr(0) . "\%"), array('_', '%'), $expression);

		return 'LIKE \'%' . $this->escape($expression) . '%\'';
	}
	
	/**
	* Вставка более одной записи одновременно
	* Есть поддержка синтаксиса INSERT .. ON DUPLICATE KEY UPDATE
	*/
	public function multi_insert($table, &$sql_ary, $on_duplicate_action = '')
	{
		if( !sizeof($sql_ary) )
		{
			return false;
		}
		
		$ary = array();
		
		foreach( $sql_ary as $id => $_sql_ary )
		{
			if( !is_array($_sql_ary) )
			{
				return $this->query('INSERT INTO ' . $table . ' ' . $this->build_array('INSERT', $sql_ary) . (($on_duplicate_action) ? ' ON DUPLICATE KEY UPDATE ' . $on_duplicate_action : ''));
			}
			
			$values = array();
			
			foreach( $_sql_ary as $key => $var )
			{
				$values[] = $this->check_value($var);
			}
			
			$ary[] = '(' . implode(', ', $values) . ')';
		}
		
		return $this->query('INSERT INTO ' . $table . ' (' . implode(', ', array_keys($sql_ary[0])) . ') VALUES ' . implode(', ', $ary) . (($on_duplicate_action) ? ' ON DUPLICATE KEY UPDATE ' . $on_duplicate_action : ''));
	}
	
	/**
	* Количество запросов
	*/
	public function num_queries($cached = false)
	{
		return ( $cached ) ? $this->num_queries['cached'] : $this->num_queries['normal'];
	}

	/**
	* Выполнение запроса к БД
	*/
	public function query($query = '', $cache_ttl = 0)
	{
		global $profiler;

		if( !$this->connect_id )
		{
			$this->connect();
		}
		
		if( $query )
		{
			global $cache;
			
			$this->query_result = ( $cache_ttl ) ? $cache->sql_load($query) : false;
			$this->add_num_queries($this->query_result);
			$start_time = microtime(true);

			if( $this->query_result === false )
			{
				if( ( $this->query_result = mysqli_query($this->connect_id, $query) ) === false )
				{
					$this->error($query);
				}
				
				if( $cache_ttl )
				{
					$cache->sql_save($query, $this->query_result, $cache_ttl);
				}
				
				$profiler->log_query($query, microtime(true) - $start_time);
			}
			else
			{
				$profiler->log_query($query, microtime(true) - $start_time, true);
			}
		}
		else
		{
			return false;
		}

		return $this->query_result;
	}
	
	public function query_limit($query, $on_page, $offset = 0, $cache_ttl = 0)
	{
		if( empty($query) )
		{
			return false;
		}
		
		$on_page = max(0, $on_page);
		$offset = max(0, $offset);
		
		$this->query_result = false;
		
		/* 0 = нет лимита */
		if( $on_page == 0 )
		{
			/**
			* -1 уже нельзя
			* Приходится использовать максимальное число
			*/
			$on_page = '18446744073709551615';
		}
		
		$query .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $on_page : $on_page);
		
		return $this->query($query, $cache_ttl);
	}
	
	/**
	* Перемещение к определенной строке
	*/
	public function rowseek($rownum, &$query_id)
	{
		global $cache;

		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( !is_object($query_id) && isset($cache->sql_rowset[$query_id]) )
		{
			return $cache->sql_rowseek($rownum, $query_id);
		}

		return ( $query_id !== false ) ? mysqli_data_seek($query_id, $rownum) : false;
	}
	

	/**
	* Число запросов к БД (для отладки)
	*/
	public function total_queries()
	{
		return $this->num_queries['total'];
	}

	/**
	* Транзакции
	*/
	public function transaction($status = 'begin')
	{
		if( !$this->connect_id )
		{
			$this->connect();
		}
		
		switch( $status )
		{
			case 'begin':

				if( $this->transaction )
				{
					$this->transactions++;
					return true;
				}

				if( false == $result = mysqli_autocommit($this->connect_id, false) )
				{
					$this->error();
				}

				$this->transaction = true;

			break;
			case 'commit':

				if( $this->transaction && $this->transactions )
				{
					$this->transactions--;
					return true;
				}

				if( !$this->transaction )
				{
					return false;
				}

				$result = mysqli_commit($this->connect_id);
				mysqli_autocommit($this->connect_id, true);

				if( !$result )
				{
					$this->error();
				}

				$this->transaction = false;
				$this->transactions = 0;

			break;
			case 'rollback':

				$result = mysqli_rollback($this->connect_id);
				mysqli_autocommit($this->connect_id, true);
				$this->transaction = false;
				$this->transactions = 0;

			break;
		}
		
		return $result;
	}

	/**
	* Установка подключения к БД
	*/
	private function connect()
	{
		$this->connect_id = mysqli_connect($this->server, $this->user, $this->password, $this->database, $this->port, $this->socket);
		$this->password = '';

		return ( $this->connect_id && $this->database ) ? $this->connect_id : $this->error();
	}

	/**
	* SQL ошибки передаём нашему обработчику
	*/
	private function error($sql = '')
	{
		global $error_ary;

		$code = ( $this->connect_id ) ? mysqli_errno($this->connect_id) : mysqli_connect_errno();
		$message = ( $this->connect_id ) ? mysqli_error($this->connect_id) : mysqli_connect_error();
		
		define('IN_SQL_ERROR', true);
		
		/* Подсветка ключевых слов */
		$sql = preg_replace('#(SELECT|INSERT INTO|UPDATE|SET|DELETE|FROM|LEFT JOIN|WHERE|AND|GROUP BY|ORDER BY|LIMIT|AS|ON)#', '<em>${1}</em>', $sql);

		$error_ary = array(
			'code' => $code,
			'sql'  => $sql,
			'text' => $message
		);

		if( $this->transaction )
		{
			$this->transaction('rollback');
		}
		
		/**
		* Автоматическое исправление таблиц
		*/
		if( $code === 145 )
		{
			if( preg_match("#Table '.+/(.+)' is marked as crashed and should be repaired#", $message, $matches) )
			{
				$this->query('REPAIR TABLE ' . $matches[1]);
			}
			elseif( preg_match("#Can't open file: '(.+).MY[ID]'\.? \(errno: 145\)#", $message, $matches) )
			{
				$this->query('REPAIR TABLE ' . $matches[1]);
			}
		}

		trigger_error(false, E_USER_ERROR);

		return $result;
	}
}
