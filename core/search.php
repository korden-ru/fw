<?php

function cmp($a,$b)
{
	if ($a == $b) return 0;
    return ($a > $b) ? -1 : 1;
}

function mysubstr($str, $start, $len)
{
	return mb_substr($str, max(0, $start), $len);
}

function Get_Entry_Count($text,$search_words)
{
	//$str = html_entity_decode($text,ENT_QUOTES,'UTF-8');
	$str = strip_tags($text);
	$i=0;
	for($k=0;$k<sizeof($search_words);$k++)
	{
		$f = mb_strpos(mb_strtoupper($str), mb_strtoupper($search_words[$k]));
		if($f!==false)
		{
			$i++;
		}
	}
	return $i;
}

function Get_Search_Text($text,$search_words)
{
	//$str = html_entity_decode($text,ENT_QUOTES,'UTF-8');
	$str = strip_tags($text);

	$res_str='';
	$i=0;
	for($k=0;$k<sizeof($search_words);$k++)
	{
		$f = mb_strpos(mb_strtoupper($str), mb_strtoupper($search_words[$k]));
		if($f!=false)
		{
			$found[$i]=$f;
			$found_word[$i]=$k;
			$i++;
		}
	}
	if($i==0) return '';
	
	for($i=0;$i<sizeof($found);$i++)
	{
		for($j=0;$j<sizeof($found)-$i-1;$j++)
		{
			if(cmp($found[$j],$found[$j+1])==-1)
			{
			 $temp=$found[$j];
			 $found[$j]=$found[$j+1];
			 $found[$j+1]=$temp;
			 
			 $temp=$found_word[$j];
			 $found_word[$j]=$found_word[$j+1];
			 $found_word[$j+1]=$temp;
			}
		}
	}
	$max_symbol = 200-3;
	$one_symbol=floor(($max_symbol)/(sizeof($found)))-3;
	$symbol_count=floor(($one_symbol-strlen($search_words[$found_word[0]]))/2);
	$start=$found[0]-$symbol_count;
	$finish=$start+$symbol_count*2+strlen($search_words[$found_word[0]]);
	for($k=0;$k<sizeof($found);$k++)
	{
		if($k<(sizeof($found)-1))
		{
		 $symbol_count=floor(($one_symbol-strlen($search_words[$found_word[$k+1]]))/2);//?????????? ?????????? ???????? ??? ????????? ???????? ??????
		 if(2*$symbol_count>=($found[$k+1]-$found[$k]))//???? 2 ???????? ?????? ?????? - ?????????? ??????????
		 {
			$finish=$found[$k+1]+strlen($search_words[$found_word[$k+1]])+$symbol_count;
		 }
		 else //????? ??????? ??????????
		 {
			$res_str=$res_str.'...'.mysubstr($str,$start,$finish-$start);
				
			$symbol_count=floor(($one_symbol-strlen($search_words[$found_word[$k+1]]))/2);
			$start=$found[$k+1]-$symbol_count;
			$finish=$start+$symbol_count*2+strlen($search_words[$found_word[$k+1]]);
		 }
		}
		else
		{
		 $res_str=$res_str.'...'.mysubstr($str,$start,$finish-$start);
		}
	}
	$res_str=$res_str.'...';
	for($k=0;$k<sizeof($search_words);$k++)
	{
		$f=0;
		$res_str1='';
		while($f<mb_strlen($res_str))
		{
			$f1 = mb_strpos(mb_strtoupper($res_str), mb_strtoupper($search_words[$k]), $f);
			if($f1===false) break;
			$res_str1=$res_str1.mb_substr($res_str,$f,$f1-$f).'<b>'.mb_substr($res_str,$f1,mb_strlen($search_words[$k])).'</b>';
			$f=$f1+mb_strlen($search_words[$k]);
		}
		$res_str1=$res_str1.mb_substr($res_str,$f,mb_strlen($res_str));
		$res_str=$res_str1;
	}
	return $res_str1;
}

function search_text($search_string,$target_fields=array(),$return=array(),$start=0,$count=10)
{
	global $db,$template;
	
	$search_array = preg_split('/ /',$search_string,-1,PREG_SPLIT_NO_EMPTY);
	
	$result_on_page = 100;
	$page = get_post('p',1) - 1;
	$start = $result_on_page * $page;
	/**
	* ???????? ?????? ?? ??
	*/
	$template->assign(array('ERROR'	=> ''));
	$i = 0;
	if(sizeof($search_array)==0)
	{
		$template->assign(array('ERROR'	=> 'Ничего не найдено'));
	}
	else
	{
		foreach($target_fields as $table=>$fields)
		{
			$sql = 'SELECT * FROM '.$table.' WHERE (';
			$f=false;
			foreach($fields as $field)
			{
				if($f==true) $sql=$sql.' OR ';
				$sql=$sql.$field.' LIKE  \'%'.$search_array[0].'%\'';
				$f=true;
				for($k=1;$k<sizeof($search_array);$k++)
				{
					$sql=$sql.' OR '.$field.' LIKE \'%'.$search_array[$k].'%\'';
				}
			}
			$sql=$sql.(isset($return[$table]['where'])?') AND '.$return[$table]['where']:')');
			$result = $db->query($sql);
			while( $row = $db->fetchrow($result) )
			{
				$search_fields='';
				foreach($fields as $field)
				{
					$search_fields=$search_fields.' '.$row[$field];
				}				
				$count=Get_Entry_Count($search_fields,$search_array);
				if($count)
				{
					$results[$i][0]=$row[$return[$table]['title']];
					$results[$i][1]=Get_Search_Text($search_fields,$search_array);
					if (strstr($return[$table]['url'], 'page1:') !== false)
					{
						$column = str_replace('page1:', '', $return[$table]['url']);
						$results[$i][2]='/'.$column.'/'.$row[$return[$table]['id']];
					}
					elseif (strstr($return[$table]['url'], 'column:') !== false)
					{
						$column = str_replace('column:', '', $return[$table]['url']);
						$results[$i][2]=$row[$column].$row[$return[$table]['id']];
					}
					else 
						$results[$i][2]=$return[$table]['url'].$row[$return[$table]['id']];
					
					$results[$i][3]=Get_Entry_Count($search_fields,$search_array);
					$i++;
				}
			}
			$db->freeresult($result);
		}
		$res=false;
		if(isset($results))
		{
			for($k=1;$k<sizeof($results);$k++)
				for($j=1;$j<sizeof($results)-$k;$j++)
				{
					if($results[$j][3]<$results[$j+1][3])
					{
					$temp=$results[$j];
					$results[$j]=$results[$j+1];
					$results[$j+1]=$temp;
					}
				}
			
			for($j=$start;($j<($start+$result_on_page))&&($j<(sizeof($results)));$j++)
			{
				$res=true;
				$template->append('search', array(
						'NUM'		=> $j+1,
						'TITLE'		=> $results[$j][0],
						'TEXT'		=> $results[$j][1],
						'URL'		=> $results[$j][2])
					);
			}
			$template->assign(array('COUNTSEARCH' => $j));
		}
	}
	return $res;
}

function search_db($search_string,$tables=array(),$target_fields=array(),$return_fields=array(),$start=0,$count=10)
{
	global $db,$template;
	/*$search_id = isset($_POST['search_id']) ? trim($_POST['search_id']) : 0;
	if($search_id == 1)
	{
		$_SESSION['search_text'] = isset($_POST['text_search']) ? trim($_POST['text_search']) : '';
	}
	$search_string = $_SESSION['search_text'];*/
	
	$search_array = preg_split('/ /',$search_string,-1,PREG_SPLIT_NO_EMPTY);
	$result_on_page = 10;
	$page = get_post('p',1) - 1;
	$start = $result_on_page * $page;
	/**
	* ???????? ?????? ?? ??
	*/
	$template->assign(array('ERROR'	=> ''));
	$i = 0;
	if(sizeof($search_array)==0)
	{
		$template->assign(array('ERROR'	=> 'Ничего не найдено'));
	}
	else
	{
		$results=array();
		foreach($tables as $table)
		{
			$sql = 'SELECT * FROM '.$table.' WHERE ';
			foreach($target_fields[$table] as $field)
			{
				$sql=$sql.$field.' LIKE \'%'.$search_array[0].'%\'';
				for($k=1;$k<sizeof($search_array);$k++)
				{
					$sql=$sql.' AND '.$field.' LIKE \'%'.$search_array[$k].'%\'';
				}
			}
			$result = $db->query($sql);
			while( $row = $db->fetchrow($result) )
			{
				$search_fields='';
				foreach($target_fields[$table] as $field)
				{
					$search_fields=$search_fields.$row[$field];
				}
				foreach($return_fields[$table] as $field)
				{
					$results[$i][$field]=$row[$field];
				}
				$i++;
			}
			$db->freeresult($result);
		}
		return $results;
	}
	return array();
}
