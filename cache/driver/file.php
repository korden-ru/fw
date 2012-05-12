<?php
/** 
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache\driver;

/**
* Хранение кэша в файлах
*/
class file
{
	public $sql_rowset = array();
	public $sql_row_pointer = array();

	protected $prefix;

	private $cache_dir;
	private $data = array();
	private $data_expires = array();
	private $is_modified = false;

	private $db;
	
	function __construct($prefix = '')
	{
		$this->set_prefix($prefix);
		$this->cache_dir = SITE_DIR . '../cache/';
	}
	
	public function _delete($filename)
	{
		$this->remove_file($this->cache_dir . $filename . '.php');
	}
	
	/**
	* Получение данных из кэша
	*/
	public function _get($filename)
	{
		$file = $this->cache_dir . $filename . '.php';
		
		if( !file_exists($file) )
		{
			return false;
		}
		
		if( false === $handle = fopen($file, 'rb') )
		{
			return false;
		}
		
		/* Пропуск заголовка */
		fgets($handle);
		
		if( $filename == $this->prefix . 'global' )
		{
			$this->data = $this->data_expires = array();
			$time = time();
			
			while( ($expires = (int) fgets($handle)) && !feof($handle) )
			{
				$bytes = substr(fgets($handle), 0, -1);
				
				if( !is_numeric($bytes) || ($bytes = (int) $bytes) === 0 )
				{
					fclose($handle);
					
					$this->data = $this->data_expires = array();
					$this->is_modified = false;
					$this->remove_file($file);
					
					return false;
				}
				
				if( $time >= $expires )
				{
					fseek($handle, $bytes, SEEK_CUR);
					continue;
				}
				
				$var = substr(fgets($handle), 0, -1);
				
				$data = fread($handle, $bytes - strlen($var));
				$data = @unserialize($data);
				
				if( $data !== false )
				{
					$this->data[$var] = $data;
					$this->data_expires[$var] = $expires;
				}
				
				fgets($handle);
			}
			
			fclose($handle);
			
			$this->is_modified = false;
			
			return true;
		}
		else
		{
			$data = false;
			$line = 0;
			
			while( ($buffer = fgets($handle)) && !feof($handle) )
			{
				$buffer = substr($buffer, 0, -1);
				
				if( !is_numeric($buffer) )
				{
					break;
				}
				
				if( $line == 0 )
				{
					$expires = (int) $buffer;
					
					if( time() >= $expires )
					{
						break;
					}
					
					if( 0 === strpos($filename, $this->prefix . 'sql_') )
					{
						fgets($handle);
					}
				}
				elseif( $line == 1 )
				{
					$bytes = (int) $buffer;
					
					/* Никогда не должно быть 0 байт */
					if( !$bytes )
					{
						break;
					}
					
					/* Чтение сериализованных данных */
					$data = fread($handle, $bytes);
					
					/* Чтение 1 байта для вызова EOF */
					fread($handle, 1);
					
					if( !feof($handle) )
					{
						/* Кто-то изменил данные */
						$data = false;
					}
					
					break;
				}
				else
				{
					/* Что-то пошло не так */
					break;
				}
				
				$line++;
			}
			
			fclose($handle);
			
			$data = ( $data !== false ) ? @unserialize($data) : $data;
			
			if( $data === false )
			{
				$this->remove_file($file);
				return false;
			}
			
			return $data;
		}
	}
	
	/**
	* Обновление/добавление записи
	*/
	public function _set($filename, $data = null, $expires = 2592000, $query = '')
	{
		$file = $this->cache_dir . $filename . '.php';
		
		if( $handle = fopen($file, 'wb') )
		{
			flock($handle, LOCK_EX);
			fwrite($handle, '<' . '?php exit; ?' . '>');
			
			if( $filename == $this->prefix . 'global' )
			{
				foreach( $this->data as $var => $data )
				{
					if( false !== strpos($var, "\r") || false !== strpos($var, "\n") )
					{
						continue;
					}
					
					$data = serialize($data);
					
					fwrite($handle, "\n" . $this->data_expires[$var] . "\n");
					fwrite($handle, strlen($data . $var) . "\n");
					fwrite($handle, $var . "\n");
					fwrite($handle, $data);
				}
			}
			else
			{
				fwrite($handle, "\n" . (time() + $expires) . "\n");
				
				if( 0 === strpos($filename, $this->prefix . 'sql_') )
				{
					fwrite($handle, $query . "\n");
				}
				
				$data = serialize($data);
				
				fwrite($handle, strlen($data) . "\n");
				fwrite($handle, $data);
			}
			
			flock($handle, LOCK_UN);
			fclose($handle);
			
			return true;
		}
		
		return false;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
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
			
			if( false === $dir = opendir($this->cache_dir) )
			{
				return;
			}
			
			while( false !== $entry = readdir($dir) )
			{
				if( 0 !== strpos($entry, $this->prefix) )
				{
					continue;
				}
				
				if( false === $handle = fopen($this->cache_dir . $entry, 'rb') )
				{
					continue;
				}
				
				/* Пропуск заголовка */
				fgets($handle);
				
				/* Пропуск времени актуальности кэша */
				fgets($handle);
				
				$query = substr(fgets($handle), 0, -1);
				
				fclose($handle);
				
				foreach( $table as $table_name )
				{
					if( false !== strpos($query, $table_name) )
					{
						$this->remove_file($this->cache_dir . $entry);
						break;
					}
				}
			}
			
			closedir($dir);
			return;
		}

		if( !$this->_exists($var) )
		{
			return;
		}

		if( isset($this->data[$var]) )
		{
			$this->is_modified = true;
			unset($this->data[$var], $this->data_expires[$var]);

			/* cache hit */
			$this->save();
		}
		elseif( $var[0] != '_' )
		{
			$this->remove_file($this->prefix . $var . '.php', true);
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
	public function load()
	{
		return $this->_get($this->prefix . 'global');
	}

	/**
	* Сброс кэша
	*/
	public function purge()
	{
		if( false === $dir = opendir($this->cache_dir) )
		{
			return;
		}
		
		while( false !== $entry = readdir($dir) )
		{
			if( 0 !== strpos($entry, $this->prefix) )
			{
				continue;
			}
			
			$this->remove_file($this->cache_dir . $entry);
		}
		
		closedir($dir);
		
		unset($this->data, $this->data_expires, $this->sql_rowset, $this->sql_row_pointer);

		$this->data = $this->data_expires = $this->sql_rowset = $this->sql_row_pointer = array();
		$this->is_modified = false;
	}
	
	/**
	* Удаление файла с кэшем
	*/
	public function remove_file($filename, $check = false)
	{
		if( $check && !is_writable($this->cache_dir) )
		{
			trigger_error('Проверьте права доступа к директории с кэшем.', E_USER_ERROR);
		}
		
		return unlink($filename);
	}
	
	/**
	* Запись данных в кэш
	*/
	public function set($var, $data, $ttl = 2592000)
	{
		if( $var[0] == '_' )
		{
			$this->data[$var] = $data;
			$this->data_expires[$var] = time() + $ttl;
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
		$this->prefix = $prefix ? $prefix . '_' : '';
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
		$query_id = sizeof($this->sql_rowset);
		
		$this->sql_rowset[$query_id] = array();
		$this->sql_row_pointer[$query_id] = 0;
		
		while( $row = $this->db->fetchrow($query_result) )
		{
			$this->sql_rowset[$query_id][] = $row;
		}
		
		$this->db->freeresult($query_result);
		
		if( $this->_set($this->prefix . 'sql_' . md5($query), $this->sql_rowset[$query_id], time() + $ttl, $query) )
		{
			$query_result = $query_id;
		}
	}

	/**
	* Удаление устаревшего кэша
	*/
	public function tidy()
	{
		if( false === $dir = opendir($this->cache_dir) )
		{
			return;
		}
		
		$time = time();
		
		while( false !== $entry = readdir($dir) )
		{
			if( 0 !== strpos($entry, $this->prefix . 'sql_') && 0 !== strpos($entry, $this->prefix . 'global') )
			{
				continue;
			}
			
			if( false === $handle = fopen($this->cache_dir . $entry, 'rb') )
			{
				continue;
			}
			
			/* Пропускаем заголовок */
			fgets($handle);
			
			$expires = (int) fgets($handle);
			
			fclose($handle);
			
			if( $time >= $expires )
			{
				$this->remove_file($this->cache_dir . $entry);
			}
		}
		
		closedir($dir);
		
		if( file_exists($this->cache_dir . $this->prefix . 'global.php') )
		{
			if( !sizeof($this->data) )
			{
				$this->load();
			}
			
			foreach( $this->data_expires as $var => $expires )
			{
				if( $time >= $expires )
				{
					$this->delete($var);
				}
			}
		}
	}

	/**
	* Выгрузка данных
	*/
	public function unload()
	{
		$this->save();
		
		unset($this->data, $this->data_expires, $this->sql_rowset, $this->sql_row_pointer);
		
		$this->data = $this->data_expires = $this->sql_rowset = $this->sql_row_pointer = array();
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
			
			if( !isset($this->data_expires[$var]) )
			{
				return false;
			}

			return (time() > $this->data_expires[$var]) ? false : isset($this->data[$var]);
		}
		else
		{
			return file_exists($this->cache_dir . $this->prefix . $var . '.php');
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
		
		if( !$this->_set($this->prefix . 'global') )
		{
			if( !is_writable($this->cache_dir) )
			{
				trigger_error('Не удалось сохранить кэш. Проверьте права доступа к директории с кэшем.', E_USER_ERROR);
			}
			
			trigger_error('Не удалось сохранить кэш', E_USER_ERROR);
		}
		
		$this->is_modified = false;
	}
}
