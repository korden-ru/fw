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
	public $is_bound = false;
	public $is_valid = true;
	public $is_csrf_token_valid = true;
	
	protected $csrf_token;
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
			'data' => array_merge(array(
				'csrf_token'          => $this->csrf_token,
				'is_bound'            => $this->is_bound,
				'is_csrf_token_valid' => $this->is_csrf_token_valid,
				'is_valid'            => $this->is_valid
			), $this->data),
			
			'fields' => $this->fields,
			'tabs'   => $this->tabs,
		)));
		
		return $this;
	}
	
	/**
	* Связывание строки из БД с полями формы
	*/
	public function bind_data($row)
	{
		return $this;
	}
	
	/**
	* Связывание пользовательского ввода с полями формы
	*/
	public function bind_request()
	{
		foreach( $this->fields as $field )
		{
			$field['value'] = $this->request->post(sprintf('%s_%s', $this->data['form_alias'], $field['field_alias']), $field['field_value']);
		}
		
		$this->is_bound = true;
		$this->is_csrf_token_valid = $this->validate_csrf_token();
		
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
				tab_id ASC,
				field_sort ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$class_name = '\\engine\\form\\field\\' . $row['field_type'];
			$this->fields[$row['field_id']] = new $class_name($row);
			$this->tabs[$row['tab_id']]['fields'][] = $row['field_id'];
		}
		
		$this->db->freeresult();
		$this->csrf_token = $this->get_csrf_token();
		
		return $this;
	}
	
	/**
	* Проверка значений полей формы
	*/
	public function validate()
	{
		if( !$this->is_bound )
		{
			trigger_error('Значения полей не связаны с полями формы.');
		}
		
		$this->is_valid = true && $this->is_csrf_token_valid;
		
		foreach( $this->fields as $field )
		{
			$this->is_valid = $field->is_valid() && $this->is_valid;
		}
		
		if( $this->is_valid )
		{
			/* Защита от повторной отправки формы */
			$this->delete_csrf_token();
		}
		
		return $this;
	}
	
	/**
	* Проверка значения CSRF-токена
	*/
	public function validate_csrf_token()
	{
		return $this->request->post(sprintf('%s_csrf_token', $this->data['form_alias']), '') === $this->csrf_token;
	}
	
	/**
	* Удаление CSRF-токена
	*/
	protected function delete_csrf_token()
	{
		unset($_SESSION['csrf'][$this->data['form_alias']]);
	}

	/**
	* Генерация нового CSRF-токена
	*/
	protected function generate_csrf_token()
	{
		return $_SESSION['csrf'][$this->data['form_alias']] = make_random_string();
	}

	/**
	* Значение CSRF-токена
	*/
	protected function get_csrf_token()
	{
		return isset($_SESSION['csrf'][$this->data['form_alias']]) ? $_SESSION['csrf'][$this->data['form_alias']] : $this->generate_csrf_token();
	}
}
