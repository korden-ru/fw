<?php
/**
*
* @package fw.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace engine\cache;

/**
* Фабрика для кэшей
*/
class factory
{
	private $prefix;
	private $type;

	function __construct($type, $prefix)
	{
		$this->prefix = $prefix;
		$this->type = $type;
	}
	
	public function get_driver()
	{
		$class = 'engine\\cache\\driver\\' . $this->type;
		
		return new $class($this->prefix);
	}
	
	public function get_service()
	{
		global $site_root_path;
		
		if( file_exists($site_root_path . 'sources/modules/cache/service.php') )
		{
			return new \app\cache\service($this->get_driver());
		}
		
		return new service($this->get_driver());
	}
}
