<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\core;

/**
* Форма
*/
class form
{
	public $is_valid = true;
	
	protected $data = array();
	protected $fields = array();
	protected $tabs = array();
	
	protected $db;
	protected $request;
	protected $template;
	
	function __construct($db, $request, $template)
	{
		$this->db       = $db;
		$this->request  = $request;
		$this->template = $template;
	}
	
	/**
	* Передача шаблонизатору данных формы
	*/
	public function append_template()
	{
		$this->template->assign('forms', array($this->data['form_alias'] => array(
			'data'   => $this->data,
			'fields' => $this->fields,
			'tabs'   => $this->tabs,
		)));
		
		return $this;
	}
	
	public function bind_request()
	{
		foreach( $this->tabs as $tab )
		{
			foreach( $tab['fields'] as $field )
			{
				
			}
		}
		
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
			$class_name = '\\engine\\form\\field\\' . $row['field_type'];
			$this->fields[$row['field_id']] = new $class_name($row);
			$this->tabs[$row['tab_id']]['fields'][] = $row['field_id'];
		}
		
		$this->db->freeresult();
		
		return $this;
	}
	
	/**
	* Проверка значений полей формы
	*/
	public function validate()
	{
		$this->is_valid = true;
		
		foreach( $this->fields as $field )
		{
			$this->is_valid = $this->is_valid && $field->validate();
		}
		
		return $this->is_valid;
	}
}
