<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\logger\handlers;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class db extends AbstractProcessingHandler
{
	private $db;

	function __construct($db, $level = LOGGER::INFO, $bubble = true)
	{
		$this->db = $db;
		
		parent::__construct($level, $bubble);
	}
	
	public function write(array $record)
	{
		$sql_ary = array(
			'user_id' => isset($record['extra']['user_id']) ? $record['extra']['user_id'] : 0,
			'channel' => $record['channel'],
			'level'   => $record['level'],
			'url'     => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			'message' => $record['formatted'],
			'time'    => $record['datetime']->format('U'),
			'ip'      => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		);
		
		$this->db->query('INSERT INTO ' . LOGS_TABLE . ' ' . $this->db->build_array('INSERT', $sql_ary));
	}
}
