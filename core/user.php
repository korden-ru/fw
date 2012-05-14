<?php

namespace engine\core;

class user implements \ArrayAccess, \IteratorAggregate, \Countable
{
	public $id;
	public $role;
	public $mail;
	public $group;
	public $userPerms;
	
	public $browser        = '';
	public $cookie         = array();
	public $ctime          = 0;
	public $data           = array();
	public $domain         = '';
	public $forwarded_for  = '';
	public $ip             = '';
	public $isp;
	public $lang           = array();
	public $page           = '';
	public $page_prev      = '';
	public $referer        = '';
	public $session_id     = '';
	
	public $is_bot = false;
	public $is_registered = false;

	protected $cache;
	protected $config;
	protected $db;
	protected $request;
	
	function __construct($request)
	{
		$this->request = $request;
		
		/**
		* Данные посетителя
		*/
		$this->browser       = $this->request->header('User-Agent');
		$this->cookie        = array('u' => 0, 'k' => '');
		$this->ctime         = time();
		$this->domain        = $this->get_server_name();
		$this->forwarded_for = $this->request->header('X-Forwarded-For');
		$this->ip            = $this->request->server('REMOTE_ADDR');
		$this->isp           = $this->request->header('Provider', 'internet');
		$this->lang['.']     = 'ru';
		$this->page          = $this->extract_page();
		$this->referer       = $this->request->header('Referer');
		
		$this->session_id = session_id();
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
	}
	
	function login_full($login,$password,$remember)	//полноценная авторизация
	{	
		//возвращает '' если логин пустой, login_activate если пользователь не активировал учетную запись и login_fail в случае неверного логина и пароля.

		//Если все хорошо, то записывает информацию в cookie и перезагружает страницу так и не разобрался в природе этого явления, но чтобы cookie записались надо перезагрузить страницу
		if($login=='') return 'login_fail';					//пароль надо передавать в md5
		if($this->check()==false)//если уже не залогинен
		{



			$sql='SELECT * 
				FROM '.SQL_PREFIX.'users 
				WHERE login = '.$this->db->check_value($login);
			$result=$this->db->query($sql);
			$row=$this->db->fetchrow($result);
			if($result->num_rows > 0)//если учетная запись найдена
			{
				
				if($row['ban']==1) return 'login_activate';//если не активирован, то выходим
				if($password == $row['password'])//если пароль верный
				{       
					if($remember==1)//если стояла галочка запомнить, то устанавливаем cookie на год
					{
						setcookie('loginfo',md5('a'.$login.'7'.$password.'@'),time()+31536000,"/");
						setcookie('login',$login,time()+31536000,"/");
					}
					else//если не стояла галочка запомнить, то устанавливаем cookie до конца сессии
					{
						setcookie('loginfo',md5('a'.$login.'7'.$password.'@'),0,"/");
						setcookie('login',$login,0,"/");
					}
					$this->id=$row['id'];
					$this->role=$row['role'];
					$this->group = $row['group'];

					$_COOKIE['loginfo']=md5('a'.$login.'7'.$password.'@');
					$_COOKIE['login']=$login;
					
					return 'login_ok';
				}
				
			}
			return "login_fail";//если логин и пароль не совпали
		}
		else
		{
			return "login_already";
		}
	}
	
	function login($target='index.php')//упрощенная функция логина
	{
		$login=isset($_POST['login'])?$_POST['login']:'';//считывает логин из параметра login
		$login_target=isset($_POST['login_target'])?$_POST['login_target']:$target;//считывает путь из параметра login_target (я в hidden input ставлю если надо не index.php). Если поля нету, то перейдет по параметру target
		$password=isset($_POST['password'])?$_POST['password']:'';//пароль из password
		$remember=isset($_POST['remember'])?1:0;//галка remember для запоминания пароля
		return $this->login_full($login,md5($password),$remember,$login_target);//входим на сайт, переводя прочитаный пароль в md5
	}
	
	function logout($target='index.php')//выход с сайта
	{
		unset($_COOKIE['login']);//удаляем cookie из массива
		unset($_COOKIE['loginfo']);
		setcookie("loginfo","",time()-100000,"/");//на всякий случай удаляем cookie другим способом (почему-то у меня первым не всегда срабатывало)
		setcookie("login","",time()-100000,"/");
		return '';
	}
	
	function register($login,$password,$mail,$array_info,$require_info,$confirm,$mode=1,$activation=false)//Функция для регистрации клиента ($array_info - массив вида "поле => значение" с инфой, которую надо заносить при регистрации
	{																		//$require_info - массив с именами полей, которые надо обязательно вводить при регистрации
		if(!$confirm) return 'register_confirm';//это галочка согласия с условиями регистрации. Если false пользователь не зарегится)))
		$sql='SELECT * FROM '.SQL_PREFIX.'users WHERE login = '.$this->db->check_value($login);//проверяем не зарегистрирован ли уже пользователь
		$result=$this->db->query($sql);
		$row=$this->db->fetchrow($result);
		$this->db->freeresult($result);
		if($row) return 'register_already';//если зарегистрирован - прекращаем процесс
		
		if(strlen($password)<6) return 'register_password';//если пароль меньше 6 символов - тоже выходим
	
		for($i=0;$i<sizeof($require_info);$i++)//проверяем заполнение всех необходимых полей
		{
			if($array_info[$require_info[$i]]=='') return 'register_info';//если поле пустое - выходим
		}
		
		$sql='INSERT INTO '.SQL_PREFIX.'users '.$this->db->build_array('INSERT',
														array(
															'role'		=>	$mode,//роль первая - простой пользователь, 0 - требует рассмотрения администратора
															'login'		=>	$login,
															'mail'		=>	$mail,
															'lastvisit'	=>	time(),
															'regtime'	=>	time(),
															'password'	=>	$password,
															'code'		=>	$activation?md5('a'.$login.'7'.$password.'@'):'_'));//если последний параметр поставить true, то будет сгенерен код активации и пока его не снять - пользователь не войдет на сайт
		$this->db->query($sql);//заносим пользователя в таблицу
		
		$sql='SELECT id FROM '.SQL_PREFIX.'users WHERE login	=	"'.$login.'"';
		$result=$this->db->query($sql);
		$row=$this->db->fetchrow($result);
		$id=$row['id'];//получаем ID пользователя
		$this->db->freeresult();
		
		if(sizeof($array_info)>0)//если в массиве с инфой что-нить есть, тогда добавляем информацию в таблицу. ID в таблице с инфой и у пользователя совпадают
		{
			$array_info['id']=$id;
		
			$sql='INSERT INTO '.SQL_PREFIX.'users_info '.$this->db->build_array('INSERT',$array_info);
			$this->db->query($sql);
		}
		
		return 'register_success';
	}
	
	function activate($hash)//активирует аккаунт с указаным активационным кодом
	{
		$sql='UPDATE '.SQL_PREFIX.'users SET code="_" WHERE code='.$this->db->check_value($hash);
		$this->db->query($sql);
	}
	
	function check()//а это самая главная функция, которая проверяет ввел пользователь пароль или еще нет
	{
		if((isset($_COOKIE['login']))&&(isset($_COOKIE['loginfo'])))//если есть 2 такие cookie
		{
			$sql='SELECT * FROM '.SQL_PREFIX.'users WHERE login = '.$this->db->check_value($_COOKIE['login']);//получаем из базы запись об этом пользователе
			$result=$this->db->query($sql);
			$row=$this->db->fetchrow($result);
			$this->db->freeresult($result);
			if($row)//если пользователь найден
			{
				if(($row['code']!='_')||($row['role']==0)) return false;//если не активирован, то считаем, что он не входил на сайт
				if(md5('a'.$_COOKIE['login'].'7'.$row['password'].'@')==$_COOKIE['loginfo'])//если cookie совпадает с информацией о пользователе, тогда заходим
				{
					$this->id=$row['id'];
					$this->role=$row['role'];
					$this->mail=$row['mail'];
					$this->group = $row['group'];
					$sql='UPDATE '.SQL_PREFIX.'users SET lastvisit='.time().' WHERE login ='.$this->db->check_value($_COOKIE['login']);//обновляем время последнего посещения
					$this->db->query($sql);
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	* Адрес страницы
	*/
	private function extract_page()
	{
		$page = ( $this->request->is_set('path') ) ? sprintf('/%s', $this->request->get('path', '')) : '';
		
		if( !$page )
		{
			$page = $this->request->server('PHP_SELF');
			$page = ( $page ) ?: $this->request->server('REQUEST_URI');
			$page = str_replace('index.php', '', $page);
		}
		
		$query_string = '';

		foreach( $_GET as $k => $v )
		{
			if( $k == 'path' || $k == 'sid' )
			{
				continue;
			}

			if( $query_string )
			{
				$query_string .= '&';
			}

			$query_string .= sprintf('%s=%s', $k, $v);
		}
		
		$page .= ( $query_string ) ? '?' . $query_string : '';
		
		return $page;
	}

	/**
	* Установка cookies
	*/
	public function set_cookie($name, $data, $time)
	{
		$cookie_name   = rawurlencode($this->config['cookie.name'] . '_' . $name) . '=' . rawurlencode($data);
		$cookie_expire = gmdate('D, d-M-Y H:i:s \\G\\M\\T', $time);
		$cookie_domain = !$this->config['cookie.domain'] || $this->config['cookie.domain'] == 'localhost' || $this->config['cookie.domain'] == '127.0.0.1' ? '' : '; domain=' . $this->config['cookie.domain'];

		header('Set-Cookie: ' . $cookie_name . (($time) ? '; expires=' . $cookie_expire : '') . '; path=' . $this->config['cookie.path'] . $cookie_domain . ((!$this->config['cookie.secure']) ? '' : '; secure') . '; HttpOnly', false);
	}
	
	protected function get_server_name()
	{
		$hostname = $this->request->header('Host') ?: $this->request->server('SERVER_NAME');
		$hostname = 0 === strpos($hostname, 'www.') ? substr($hostname, 4) : $hostname;
		$hostname = (false !== $pos = strpos($hostname, ':')) ? substr($hostname, 0, $pos) : $hostname;
		
		return $hostname;
	}

	/**
	* Реализация интерфейса Countable
	*/
	public function count()
	{
		return sizeof($this->data);
	}
	
	/**
	* Реализация интерфейса IteratorAggregate
	*/
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
	
	/**
	* Реализация интерфейса ArrayAccess
	*/
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}
	
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		trigger_error('Функция unset() не поддерживается');
	}
}
