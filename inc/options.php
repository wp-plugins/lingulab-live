<?php
/**
 * LinguLab WordPress Plugin
 * 
 * Copyright 2009 Tom Klingenberg <lastflood.com>
 *
 * @author Tom Klingenberg
 */

class lingulabLiveOptions_Exception extends Exception
{}


/**
 * options class
 *
 * LinguLab webservice implementation (proxy/model) 
 */
class lingulabLiveOptions
{
	/**
	 * instances static store
	 * @var array of lingulabLiveUseroptions
	 */	
	static $_instances = array();
	
	/**
	 * userid local store
	 * 
	 * @var int
	 */
	private $_userid = 0;

	/**
	 * local helper function to create wp-side
	 * metadata key for a user based on a user's
	 * optionname (plugin).
	 * 
	 * @param string $name
	 * @return string
	 */
	private function _key($name)
	{
		return sprintf('lingulab_%s', $name);
	}
	
	/**
	 * instance getter
	 * 
	 * @param int $userid
	 * @return lingulabLiveOptions
	 */
	public static function getInstance($userid)
	{		
		$key = sprintf('userid-%d', $userid);
		
		if ( false == isset($_instances[$key]) )
		{
			$_instances[$key] = new lingulabLiveOptions($userid);
		}
		
		return $_instances[$key];	
	}
	
	/**
	 * 
	 * @return lingulabLiveOptions
	 */
	public static function my()
	{
		$user   = wp_get_current_user();
		
		if (!is_object($user))
		{
			throw new lingulabLiveOptions_Exception('No current user.');
		}
					 			
		$userid = $user->ID;
		
		return  lingulabLiveOptions::getInstance($userid);
	}
	
	/**
	 * constcuctor
	 * 
	 * @param int $userid (optional) userid of user
	 */
	protected function __construct($userid = null)
	{
		if (is_null($userid))
		{
			$user   = wp_get_current_user();
			if (!is_object($user))
			{
				throw new lingulabLiveOptions_Exception('No current user.');
			}			 			
			$userid = $user->ID;
		} else {
			$userid = (int) $userid;
			$userid = max(0, $userid);
		}
		$this->_userid = $userid;
	}
	
	/**
	 * magic getter
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)	
	{		
		return get_usermeta($this->_userid, $this->_key($name));
	}
	
	/**
	 * magic setter
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{		
		$r = update_usermeta($this->_userid, $this->_key($name), $value);		
	}
}
