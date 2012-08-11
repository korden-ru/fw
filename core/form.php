<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\core;

/**
* Автозагрузчик классов
*/
class form
{
	protected $data = array();
	protected $tabs = array();
	
	protected $db;
	protected $template;
	
	function __construct($db, $template)
	{
		$this->db       = $db;
		$this->template = $template;
	}
	
	/**
	* Передача шаблонизатору данных формы
	*/
	public function append_template()
	{
		$this->template->assign('forms', array($this->data['form_alias'] => array(
			'data'   => $this->data,
			'tabs'   => $this->tabs,
		)));
		
		return $this;
	}
	
	/**
	* Извлечение информации о форме
	*/
	public function get_form($alias)
	{
		if( !$alias )
		{
			trigger_error('Не указан псевдоним формы.');
		}
		
		$sql = '
			SELECT
				*
			FROM
				' . FORMS_TABLE . '
			WHERE
				form_alias = ' . $this->db->check_value($alias);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( !$row )
		{
			trigger_error('Форма не найдена.');
		}
		
		$this->data = $row;
		
		/* Загрузка вкладок */
		$sql = '
			SELECT
				*
			FROM
				' . FORM_TABS_TABLE . '
			WHERE
				form_id = ' . $this->db->check_value($this->data['form_id']) . '
			ORDER BY
				tab_sort ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$row['fields'] = array();
			
			$this->tabs[$row['tab_id']] = $row;
		}
		
		$this->db->freeresult();
		
		if( empty($this->tabs) )
		{
			trigger_error('У формы нет вкладок.');
		}
		
		/* Загрузка полей формы */
		$sql = '
			SELECT
				*
			FROM
				' . FORM_FIELDS_TABLE . '
			WHERE
				form_id = ' . $this->db->check_value($this->data['form_id']) . '
			ORDER BY
				field_sort ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->tabs[$row['tab_id']]['fields'][] = $row;
		}
		
		$this->db->freeresult();
		
		return $this;
	}
}
