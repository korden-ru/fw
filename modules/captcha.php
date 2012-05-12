<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\modules;

use app\models\page;
use engine\captcha\service as captcha_service;

/**
* Вывод кода подтверждения (каждый раз нового)
*/
class captcha extends page
{
	public function index()
	{
		$class = '\\engine\\captcha\\driver\\' . $this->config['confirm.type'];
		
		$captcha = new captcha_service(new $class(), $this->config, $this->request);
		$captcha->send();
		
		garbage_collection(false);
		exit;
	}
}
