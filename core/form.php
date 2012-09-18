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
	protected $last_tab = 0;
	protected $tabs = array();
	
	protected $config;
	protected $db;
	protected $request;
	protected $template;
	
	function __construct($config, $db, $request, $template)
	{
		$this->config   = $config;
		$this->db       = $db;
		$this->request  = $request;
		$this->template = $template;
	}
	
	public function add_field($row, $tab_id = false)
	{
		if( false == $type = @$row['field_type'] ?: @$row['type'] ?: '' )
		{
			trigger_error('При создании поля не было указано обязательное поле type.');
		}
		
		if( empty($this->data) )
		{
			trigger_error('Перед добавлением полей следует создать форму.');
		}
		
		if( false === $tab_id && !$this->last_tab )
		{
			/**
			* При ручном создании формы вкладка будет создана
			* автоматически при добавлении первого поля
			*/
			$this->add_tab();
		}
		
		/* При ручном создании формы поле попадает в последнюю вкладку */
		$tab_id = false === $tab_id ? $this->last_tab : $tab_id;
		
		$class_name = '\\engine\\form\\field\\' . $type;
		
		$this->fields[] = new $class_name(array(
			'field_type'             => $type,
			'field_title'            => @$row['field_title'] ?: @$row['title'] ?: '',
			'field_alias'            => @$row['field_alias'] ?: @$row['alias'] ?: @$row['name'] ?: '',
			'field_required'         => @$row['field_required'] ?: @$row['required'] ?: 0,
			'field_disabled'         => @$row['field_disabled'] ?: @$row['disabled'] ?: 0,
			'field_readonly'         => @$row['field_readonly'] ?: @$row['readonly'] ?: 0,
			'field_multiple'         => @$row['field_multiple'] ?: @$row['multiple'] ?: 0,
			'field_rounding_mode'    => @$row['field_rounding_mode'] ?: @$row['rounding_mode'] ?: 0,
			'field_precision'        => @$row['field_precision'] ?: @$row['precision'] ?: 2,
			'field_always_empty'     => @$row['field_always_empty'] ?: @$row['always_empty'] ?: 0,
			'field_default_protocol' => @$row['field_default_protocol'] ?: @$row['default_protocol'] ?: '',
			'field_width'            => @$row['field_width'] ?: @$row['width'] ?: '',
			'field_height'           => @$row['field_height'] ?: @$row['height'] ?: '',
			'field_autofocus'        => @$row['field_autofocus'] ?: @$row['autofocus'] ?: 0,
			'field_tabindex'         => @$row['field_tabindex'] ?: @$row['tabindex'] ?: 0,
			'field_min'              => @$row['field_min'] ?: @$row['min'] ?: '',
			'field_max'              => @$row['field_max'] ?: @$row['max'] ?: '',
			'field_pattern'          => @$row['field_pattern'] ?: @$row['pattern'] ?: '',
			'field_value'            => @$row['field_value'] ?: @$row['value'] ?: '',
			'field_values'           => @$row['field_values'] ?: @$row['values'] ?: '',
			'field_placeholder'      => @$row['field_placeholder'] ?: @$row['placeholder'] ?: '',
			'field_prepend'          => @$row['field_prepend'] ?: @$row['prepend'] ?: '',
			'field_append'           => @$row['field_append'] ?: @$row['append'] ?: '',
			'field_help_inline'      => @$row['field_help_inline'] ?: @$row['help_inline'] ?: '',
			'field_help'             => @$row['field_help'] ?: @$row['help'] ?: '',
			'field_repeated'         => @$row['field_repeated'] ?: @$row['repeated'] ?: '',
			'field_invalid_message'  => @$row['field_invalid_message'] ?: @$row['invalid_message'] ?: '',
			'field_attr'             => @$row['field_attr'] ?: @$row['attr'] ?: '',
		), $this->config);
		
		$this->tabs[$tab_id]['fields'][] = sizeof($this->fields) - 1;
		
		return $this;
	}
	
	public function add_form($row)
	{
		if( false == $alias = @$row['form_alias'] ?: @$row['alias'] ?: '' )
		{
			trigger_error('При создании формы не было указано обязательное поле alias.');
		}
		
		$this->data = array(
			'form_title'        => @$row['form_title'] ?: @$row['title'] ?: '',
			'form_alias'        => $alias,
			'form_email'        => @$row['form_email'] ?: @$row['email'] ?: '',
			'form_class'        => @$row['form_class'] ?: @$row['class'] ?: '',
			'form_action'       => @$row['form_action'] ?: @$row['action'] ?: '',
			'form_enctype'      => @$row['form_enctype'] ?: @$row['enctype'] ?: '',
			'form_method'       => @$row['form_method'] ?: @$row['method'] ?: 'post',
			'form_message'      => @$row['form_message'] ?: @$row['message'] ?: '',
			'form_fields_width' => @$row['form_fields_width'] ?: @$row['fields_width'] ?: '',
			'form_submit_text'  => @$row['form_submit_text'] ?: @$row['submit_text'] ?: '',
			'form_submit_class' => @$row['form_submit_class'] ?: @$row['submit_class'] ?: '',
			'form_captcha'      => @$row['form_captcha'] ?: @$row['captcha'] ?: 0,
		);

		$this->csrf_token = $this->get_csrf_token();
		
		return $this;
	}
	
	public function add_tab($row = array(), $tab_id = false)
	{
		if( false === $tab_id )
		{
			$this->last_tab++;
			$tab_id = $this->last_tab;
		}
		
		$this->tabs[$tab_id] = array(
			'tab_title' => @$row['tab_title'] ?: @$row['title'] ?: '',
			
			'fields' => array(),
		);
		
		return $this;
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
		foreach( $this->fields as $field )
		{
			$field['value'] = isset($row[$field['field_alias']]) ? $row[$field['field_alias']] : $field['value'];
		}
		
		return $this;
	}
	
	/**
	* Связывание пользовательского ввода с полями формы
	*/
	public function bind_request()
	{
		foreach( $this->fields as $field )
		{
			switch( $this->data['form_method'] )
			{
				case 'get':  $method = 'get'; break;
				case 'post': $method = 'post'; break;
				default:     $method = 'variable'; break;
			}
			
			$field->set_value($this->request->$method(sprintf('%s_%s', $this->data['form_alias'], $field['field_alias']), $field->get_default_value(true)));
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
			$this->add_tab($row, $row['tab_id']);
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
			$this->add_field($row, $row['tab_id']);
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
