<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\config;

/**
* Настройки сайта, хранящиеся в БД
*/
class db extends config
{
	protected $domain;
	protected $language;
	protected $site_id;
	protected $site_vars;
	protected $table;

	protected $cache;
	protected $db;
	
	function __construct($cache, $db, $site_info, $table = false)
	{
		if( !is_array($site_info) )
		{
			trigger_error('Сайт не найден', E_USER_ERROR);
		}
		
		$this->cache = $cache;
		$this->db    = $db;
		
		$this->domain   = $site_info['domain'];
		$this->language = $site_info['language'];
		$this->site_id  = $site_info['id'];
		$this->table    = $table ?: CONFIG_TABLE;
		
		parent::__construct(array_merge($this->load_config(0), $this->load_config($this->site_id)));
	}
	
	/**
	* Удаление настройки
	*/
	public function delete($key, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		$sql = '
			DELETE
			FROM
				' . $this->table . '
			WHERE
				config_name = ' . $this->db->check_value($key) . '
			AND
				site_id = ' . $this->db->check_value($site_id);
		$this->db->query($sql);
		
		if( $site_id === $this->site_id )
		{
			/* Настройки текущего сайта */
			unset($this->config[$key]);
			$this->cache->_delete(sprintf('%s_config_%s', $this->domain, $this->language));
		}
		elseif( $site_id === 0 )
		{
			/* Настройки движка */
			if( !isset($this->site_vars[$key]) )
			{
				unset($this->config[$key]);
			}
			
			$this->cache->_delete('fw_config');
		}
		elseif( $site_id > 0 && $site_id !== $this->site_id )
		{
			/* Настройки другого сайта */
			$site_info = get_site_info_by_id($site_id);
			
			$this->cache->_delete(sprintf('%s_config_%s', $site_info['domain'], $site_info['language']));
		}
	}
	
	/**
	* Увеличение значения настройки (счетчика)
	*/
	public function increment($key, $increment = 1, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		if( $site_id !== 0 && $site_id !== $this->site_id )
		{
			trigger_error('Метод increment можно вызывать только для текущего сайта и движка');
		}
		
		if( $site_id === $this->site_id && !isset($this->site_vars[$key]) )
		{
			/* Настройка текущего сайта */
			$this->set($key, 0);
		}
		elseif( $site_id === 0 && !isset($this->config[$key]) )
		{
			/* Настройка движка */
			$this->set($key, 0, 0);
		}
		
		$sql = '
			UPDATE
				' . $this->table . '
			SET
				config_value = config_value + ' . ((int) $increment) . '
			WHERE
				config_name = ' . $this->db->check_value($key) . '
			AND
				site_id = ' . $this->db->check_value($site_id);
		$this->db->query($sql);
		
		if( $site_id > 0 )
		{
			/* Настройки сайта */
			$this->config[$key] += $increment;
			$this->cache->_delete(sprintf('%s_config_%s', $this->domain, $this->language));
		}
		elseif( $site_id === 0 )
		{
			/* Настройки движка */
			if( !isset($this->site_vars[$key]) )
			{
				/**
				* Текущее значение обновляется только если
				* сайт не переопределил настройку
				*/
				$this->config[$key] += $increment;
			}
			
			$this->cache->_delete('fw_config');
		}
	}

	/**
	* Установка нового значения настройки
	*/
	public function set($key, $value, $site_id = false)
	{
		$this->set_atomic($key, false, $value, $site_id);
	}
	
	/**
	* Установка нового значения только если предыдущее совпадает или вовсе отсутствует
	*/
	public function set_atomic($key, $old_value, $new_value, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		$sql = '
			UPDATE
				' . $this->table . '
			SET
				config_value = ' . $this->db->check_value($new_value) . '
			WHERE
				config_name = ' . $this->db->check_value($key) . '
			AND
				site_id = ' . $this->db->check_value($site_id);
		
		if( $old_value !== false )
		{
			$sql .= ' AND config_value = ' . $this->db->check_value($old_value);
		}
		
		$this->db->query($sql);
		
		if( !$this->db->affected_rows() )
		{
			if( ($site_id === $this->site_id && isset($this->site_vars[$key])) ||
				($site_id === 0 && isset($this->config[$key])) )
			{
				return false;
			}
		}
		
		if( ($site_id === $this->site_id && !isset($this->site_vars[$key])) ||
			($site_id === 0 && !isset($this->config[$key]) && !isset($this->site_vars[$key])) ||
			($site_id > 0 && $site_id !== $this->site_id) )
		{
			$insert = ( $site_id > 0 && $site_id !== $this->site_id ) ? 'INSERT IGNORE' : 'INSERT';
			
			$sql = $insert . ' INTO ' . $this->table . ' ' . $this->db->build_array('INSERT', array(
				'config_name'  => $key,
				'config_value' => $new_value,
				'site_id'      => $site_id
			));
			$this->db->query($sql);
		}
		
		if( $site_id === $this->site_id )
		{
			/* Настройки текущего сайта */
			$this->config[$key] = $new_value;
			$this->site_vars[$key] = true;
			$this->cache->_delete(sprintf('%s_config_%s', $this->domain, $this->language));
		}
		elseif( $site_id === 0 )
		{
			/* Настройки движка */
			if( !isset($this->site_vars[$key]) )
			{
				/**
				* Текущее значение обновляется только если
				* сайт не переопределил настройку
				*/
				$this->config[$key] = $new_value;
			}
			
			$this->cache->_delete('fw_config');
		}
		elseif( $site_id > 0 && $site_id !== $this->site_id )
		{
			/* Настройки другого сайта */
			$site_info = get_site_info_by_id($site_id);
			
			$this->cache->_delete(sprintf('%s_config_%s', $site_info['domain'], $site_info['language']));
		}
		
		return true;
	}
	
	/**
	* Загрузка настроек сайта из БД
	*/
	private function load_config($site_id)
	{
		$cache_entry = 0 === $site_id ? 'fw_config' : sprintf('%s_config_%s', $this->domain, $this->language);
		
		if( false === $config = $this->cache->_get($cache_entry) )
		{
			$sql = '
				SELECT
					config_name,
					config_value
				FROM
					' . $this->table . '
				WHERE
					site_id = ' . $this->db->check_value($site_id);
			$this->db->query($sql);
			$config = array();

			while( $row = $this->db->fetchrow() )
			{
				$config[$row['config_name']] = $row['config_value'];
			}

			$this->db->freeresult();
			$this->cache->_set($cache_entry, $config);
		}
		
		if( $site_id )
		{
			foreach( $config as $key => $value )
			{
				$this->site_vars[$key] = true;
			}
		}
		
		return $config;
	}
}
