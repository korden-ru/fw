<?php
/**
* @package fw.korden.net
* @copyright (c) 2012
*/

namespace engine\helpers\traverse;

/**
* Обход древовидной структуры
*/
class tree
{
	protected $branch = array();
	protected $edge = array();
	protected $row = array();
	protected $tree = array();
	
	protected $depth = 0;
	protected $right_id = 0;
	
	protected $return_as_tree = false;
	
	function __construct($return_as_tree = false)
	{
		$this->return_as_tree = $return_as_tree;
	}
	
	public function get_tree_data()
	{
		return $this->tree;
	}
	
	/**
	* Обработка одного элемента массива
	*/
	public function process_node($row = array())
	{
		if( !empty($row) )
		{
			$this->row = $row;
		}
		
		/* Пропуск ветви */
		if( $this->right_id )
		{
			if( $this->row['left_id'] < $this->right_id )
			{
				return;
			}
			
			$this->right_id = false;
		}
		
		/* Следует ли пропустить ветвь дерева */
		if( $this->skip_condition() )
		{
			$this->right_id = $this->row['right_id'];
			return;
		}
		
		/* Уменьшение глубины */
		if( $this->depth && $this->row['left_id'] > $this->edge[$this->depth] )
		{
			for( $i = $this->depth; $i > 0; $i-- )
			{
				if( $this->row['left_id'] > $this->edge[$i] )
				{
					array_pop($this->edge);
					$this->depth--;
					$this->on_depth_decrease();
				}
			}
		}

		/* Увеличение глубины */
		$this->depth++;
		$this->edge[$this->depth] = $this->row['right_id'];
		$this->on_depth_increase();
		
		if( false === $data = $this->get_data() )
		{
			return;
		}
		
		$this->tree_append($data);
		$this->on_tree_append();
	}

	/**
	* Обработка многомерного массива
	*/
	public function process_nodes($rows)
	{
		if( !is_array($rows) || !isset($rows[0]) )
		{
			return false;
		}
		
		foreach( $rows as $this->row )
		{
			$this->process_node();
		}
	}
	
	/**
	* Данные одного узла дерева
	*/
	protected function get_data()
	{
		return $this->return_as_tree ? array('children' => array()) : '';
	}
	
	/**
	* Действия при уменьшении уровня
	*/
	protected function on_depth_decrease()
	{
	}
	
	/**
	* Действия при увеличении уровня
	*/
	protected function on_depth_increase()
	{
	}
	
	/**
	* Действия после присоединения нового узла к дереву
	*/
	protected function on_tree_append()
	{
	}
	
	/**
	* Условие пропуска ветви дерева
	*/
	protected function skip_condition()
	{
		return false;
	}
	
	/**
	* Способы присоединения узла к дереву
	*/
	protected function tree_append($data)
	{
		/* Формирование списка */
		if( !$this->return_as_tree )
		{
			$this->tree[] = $data;
			return true;
		}
		
		/* Формирование дерева */
		if( $this->depth === 1 )
		{
			unset($this->branch);
			$this->branch = array();

			$i = sizeof($this->tree);
			$this->tree[$i] = $data;
			$this->branch[$this->depth] =& $this->tree[$i];
		}
		else
		{
			$i = sizeof($this->branch[$this->depth - 1]['children']);
			$this->branch[$this->depth - 1]['children'][$i] = $data;
			$this->branch[$this->depth] =& $this->branch[$this->depth - 1]['children'][$i];
		}
	}
}
