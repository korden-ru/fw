<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\core;

/**
* Класс для хранения информации о скрипте и ходе его выполнения
*/
class console
{
	protected $error_count   = 0;
	protected $file_count    = 0;
	protected $file_size     = 0;
	protected $file_largest  = 0;
	protected $log_count     = 0;
	protected $logs_count    = 0;
	protected $memory_count  = 0;
	protected $memory_total  = 0;
	protected $memory_used   = 0;
	protected $speed_allowed = 0;
	protected $speed_count   = 0;
	protected $speed_total   = 0;
	protected $query_cached  = 0;
	protected $query_count   = 0;
	protected $query_time    = 0;

	protected $logs = array();
	protected $queries = array();

	/**
	* Лог пользовательских данных
	*/
	public function log($data)
	{
		$this->logs[] = array(
			'data' => $data,
			'type' => 'log'
		);

		$this->log_count++;
	}

	/**
	* Лог расхода памяти
	*/
	public function log_memory($object = false, $name = 'php')
	{
		$this->logs[] = array(
			'data'      => $object ? strlen(serialize($object)) : memory_get_usage(),
			'type'      => 'memory',
			'name'      => $name,
			'data_type' => gettype($object)
		);

		$this->memory_count++;
	}

	/**
	* Лог ошибок
	*/
	public function log_error($message, $line, $file)
	{
		$call_stack = '';
		
		if( function_exists('xdebug_print_function_stack') )
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace(array('/srv/www/vhosts'), array(''), ob_get_clean());
		}
		
		$this->logs[] = array(
			'call_stack' => $call_stack,
			'data'       => $message,
			'type'       => 'error',
			'file'       => $file,
			'line'       => $line
		);

		$this->error_count++;
	}

	/**
	* Лог времени
	*/
	public function log_speed($name = 'label')
	{
		$this->logs[] = array(
			'data' => microtime(true),
			'type' => 'speed',
			'name' => $name
		);

		$this->speed_count++;
	}

	/**
	* Лог запросов к БД
	*/
	public function log_query($sql, $time, $cached = false)
	{
		$this->queries[] = array(
			'cached' => $cached,
			'sql'    => preg_replace('#[\n\r\s\t]+#', ' ', $sql),
			'time'   => $this->get_readable_time($time * 1000)
		);

		$this->query_count++;
		$this->query_time += $time * 1000;
		
		if( $cached )
		{
			$this->query_cached++;
		}
	}
}

class profiler extends console
{
	private $output = array();
	private $start_time;
	
	private $template;

	/**
	* Время запуска профайлера
	*/
	function __construct($template)
	{
		$this->start_time = microtime(true);
		$this->template   = $template;
	}
	
	/**
	* Получение и печать данных профайлера
	*/
	public function display()
	{
		if( PHP_SAPI == 'cli' )
		{
			return;
		}
		
		$this->get_console_data()
			->get_file_data()
			->get_memory_data()
			->get_query_data()
			->get_speed_data()
			->display_profiler();
	}
	
	/**
	* Отправка данных внешнему профайлеру
	*/
	public function send_stats($ip, $port)
	{
		if( PHP_SAPI == 'cli' )
		{
			return;
		}
		
		if( false === $fp = fsockopen('udp://' . $ip, $port) )
		{
			return false;
		}
		
		if( !$this->memory_used )
		{
			$this->get_console_data();
			$this->get_file_data();
			$this->get_memory_data();
			$this->get_query_data();
			$this->get_speed_data();
		}

		fwrite($fp, json_encode(array(
			// 'domain' => $user->domain,
			// 'page'   => $user->page,
			
			'logs' => $this->output['logs'],
			
			'file_count'    => $this->file_count,
			'file_size'     => $this->file_size,
			'file_largest'  => $this->file_largest,
			'log_count'     => $this->log_count,
			'logs_count'    => sizeof($this->output['logs']),
			'error_count'   => $this->error_count,
			'memory_count'  => $this->memory_count,
			'memory_total'  => $this->memory_total,
			'memory_used'   => $this->memory_used,
			'speed_allowed' => $this->speed_allowed,
			'speed_count'   => $this->speed_count,
			'speed_total'   => intval($this->speed_total),
			'query_cached'  => $this->query_cached,
			'query_count'   => $this->query_count,
			'query_time'    => sprintf('%.3f', $this->query_time),
		)));
		
		fclose($fp);
	}

	/**
	* Время в определенном формате
	*/
	protected function get_readable_time($time)
	{
		return sprintf('%.3f ms', $time);
	}

	/**
	* Сообщения, выведенные в консоль
	*/
	private function get_console_data()
	{
		foreach( $logs = $this->logs as $key => $log )
		{
			switch( $log['type'] )
			{
				case 'log': $logs[$key]['data'] = print_r($log['data'], true); break;
				case 'memory': $logs[$key]['data'] = humn_size($log['data'], 2); break;
				case 'speed': $logs[$key]['data'] = $this->get_readable_time(($log['data'] - $this->start_time) * 1000); break;
			}
		}

		$this->output['logs'] = $logs;
		
		return $this;
	}

	/**
	* Список подключенных файлов
	*/
	private function get_file_data()
	{
		$file_list = array();
		$this->file_count = 0;

		foreach( get_included_files() as $key => $file )
		{
			if( false !== strpos($file, '/Twig/') || false !== strpos($file, '/lib/') )
			{
				continue;
			}
			
			$size = filesize($file);

			$file_list[] = array(
				'name' => str_replace(array('/srv/www/vhosts'), array(''), $file),
				'size' => humn_size($size, 2)
			);

			$this->file_size += $size;
			$this->file_largest = $size > $this->file_largest ? $size : $this->file_largest;
			$this->file_count++;
		}

		$this->output['files'] = $file_list;
		
		return $this;
	}

	/**
	* Общий расход памяти
	*/
	private function get_memory_data()
	{
		$this->memory_used  = memory_get_peak_usage();
		$this->memory_total = ini_get('memory_limit');
		
		return $this;
	}

	/**
	* Запросы к БД
	*/
	private function get_query_data()
	{
		$this->output['queries'] = $this->queries;
		
		return $this;
	}

	/**
	* Скорость выполнения страницы
	*/
	private function get_speed_data()
	{
		$this->speed_allowed = ini_get('max_execution_time');
		$this->speed_total   = (microtime(true) - $this->start_time) * 1000;
		
		return $this;
	}

	/**
	* Вывод собранных профайлером данных на страницу
	*/
	private function display_profiler()
	{
		$this->template->assign(array(
			'profiler_logs'    => $this->output['logs'],
			'profiler_files'   => $this->output['files'],
			'profiler_queries' => $this->output['queries'],
			
			'FILE_COUNT'    => $this->file_count,
			'FILE_SIZE'     => humn_size($this->file_size),
			'FILE_LARGEST'  => humn_size($this->file_largest),
			'LOG_COUNT'     => $this->log_count,
			'LOGS_COUNT'    => sizeof($this->output['logs']),
			'ERROR_COUNT'   => $this->error_count,
			'MEMORY_COUNT'  => $this->memory_count,
			'MEMORY_TOTAL'  => $this->memory_total,
			'MEMORY_USED'   => humn_size($this->memory_used),
			'SPEED_ALLOWED' => $this->speed_allowed,
			'SPEED_COUNT'   => $this->speed_count,
			'SPEED_TOTAL'   => $this->get_readable_time($this->speed_total),
			'QUERY_CACHED'  => $this->query_cached,
			'QUERY_COUNT'   => $this->query_count,
			'QUERY_TIME'    => $this->get_readable_time($this->query_time),

			'FILE_COUNT_TEXT'  => plural($this->file_count, 'файл;файла;файлов'),
			'QUERY_COUNT_TEXT' => plural($this->query_count, 'запрос;запроса;запросов')
		));
		
		$this->template->display('profiler.html');
	}
}
