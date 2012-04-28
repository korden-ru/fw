<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cron;

/**
* Задачи по расписанию
*/
class manager
{
	private $cron_dir;
	private $log_dir;

	private $cron_allowed;
	private $cron_running;
	private $deadlock_timeout = 900;
	private $start_time;
	private $task_time_limit = 300;
	private $tasks = array();
	private $tasks_timeout = 1;

	private $cache;
	private $config;
	private $db;

	function __construct($cache, $config, $db)
	{
		$this->cache  = $cache;
		$this->config = $config;
		$this->db     = $db;

		$this->start_time = time();

		$this->cron_dir = SITE_DIR . '../modules/cron/';
		$this->log_dir  = SITE_DIR . '../logs/cron/';

		$this->cron_allowed = $this->log_dir . 'allowed';
		$this->cron_running = $this->log_dir . 'running';
	}

	/**
	* Освобождение блокировки
	*/
	public function release_file_lock()
	{
		rename($this->cron_running, $this->cron_allowed);
		touch($this->cron_allowed);
	}

	/**
	* Выполнение задач
	*/
	public function run()
	{
		if( file_exists($this->cron_running) )
		{
			/**
			* До сих пор выполняются задачи в другом процессе
			* Выходим и ждем своей очереди
			*/
			return;
		}

		if( !$this->get_file_lock() )
		{
			return;
		}

		register_shutdown_function(array($this, 'release_file_lock'));

		$this->track_running('start');
		$this->load_tasks();
		$this->log(sprintf('Найдено готовых к запуску задач: %d', sizeof($this->tasks)));

		if( $this->tasks )
		{
			foreach( $this->tasks as $task )
			{
				$this->set_includes_dir($task['site_id']);
				$this->log(sprintf('Выполнение задачи "%s" [%s] на сайте: #%d', $task['cron_title'], $task['cron_script'], $task['site_id']));
				set_time_limit($this->task_time_limit);

				/* Выполнение задачи */
				$cron_class = '\\app\\cron\\' . $task['cron_script'];
				$cron = new $cron_class($task);
				$cron->_set_cache($this->cache)
					->_set_config($this->config)
					->_set_db($this->db);
				
				if( $cron->run() )
				{
					/* Установка времени следующего запуска */
					$this->set_next_run_time($task['cron_id'], $task['cron_schedule']);
					$this->log('Задача выполнена');
				}
				else
				{
					$this->log('Не удалось выполнить задачу');
				}

				sleep($this->tasks_timeout);
			}
		}

		$this->track_running('end');
		$this->log('Завершение работы менеджера задач');
	}

	/**
	* Получаем блокировку для выполнения задач
	*/
	private function get_file_lock()
	{
		if( file_exists($this->cron_allowed) )
		{
			return rename($this->cron_allowed, $this->cron_running);
		}
		elseif( file_exists($this->cron_running) )
		{
			$this->release_deadlock();
		}

		return false;
	}

	/**
	* Загрузка задач, готовых к выполнению
	*/
	private function load_tasks()
	{
		$sql = '
			SELECT
				*
			FROM
				' . CRON_TABLE . '
			WHERE
				cron_active = 1
			AND
				next_run <= ' . $this->start_time . '
			ORDER BY
				site_id ASC,
				run_order ASC';
		$result = $this->db->query($sql);
		$this->tasks = $this->db->fetchall($result);
		$this->db->freeresult($result);
	}
	
	/**
	* Лог операций
	*/
	private function log($text)
	{
		printf("%s: %s\n", date('Y-m-d H:i:s'), $text);
	}

	/**
	* Выход из тупика
	*/
	private function release_deadlock()
	{
		if( !file_exists($this->cron_running) || time() - filemtime($this->cron_running) < $this->deadlock_timeout )
		{
			return;
		}

		/* Разблокировка */
		$this->release_file_lock();
	}
	
	/**
	* Задачи следующего проекта. Необходимо сменить site_root_path,
	* чтобы соориентировать загрузчик классов
	*/
	private function set_includes_dir($site_id)
	{
		static $id = 0;
		
		if( $site_id != $id )
		{
			$id = (int) $site_id;
			
			$sql = '
				SELECT
					config_value
				FROM
					' . CONFIG_TABLE . '
				WHERE
					config_name = ' . $this->db->check_value('site_dir') . '
				AND
					site_id = ' . $id;
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			// $site_root_path = $row['config_value'];
			
			/* Загрузка настроек сайта */
			// require_once($site_root_path . 'config.php');
			// $this->cache->set_prefix($acm_prefix);
		}
	}

	/**
	* Установка времени следующего запуска
	*/
	private function set_next_run_time($cron_id, $cron_schedule)
	{
		$next_run = date_create();
		date_modify($next_run, $cron_schedule);

		$sql = '
			UPDATE
				' . CRON_TABLE . '
			SET
				last_run = UNIX_TIMESTAMP(),
				next_run = ' . date_timestamp_get($next_run) . ',
				run_counter = run_counter + 1
			WHERE
				cron_id = ' . $cron_id;
		$this->db->query($sql);
	}

	/**
	* Отслеживание процесса выполнения задач
	*
	* В случае возникновения ошибок в папке логов останется файл
	*/
	private function track_running($mode)
	{
		$startmark = sprintf('%scron_started_at_%s', $this->log_dir, date('Y-m-d_H-i-s', $this->start_time));

		if( $mode == 'start' )
		{
			touch($this->cron_running);
			touch($startmark);
		}
		elseif( $mode == 'end' )
		{
			unlink($startmark);
		}
	}
}
