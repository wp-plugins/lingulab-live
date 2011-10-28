<?php
/**
 * LinguLab WordPress Plugin
 * 
 * Copyright 2009 Tom Klingenberg <lastflood.com>
 *
 * @author Tom Klingenberg
 */


/**
 * lingulabLiveWebserviceClient_Exception class
 */
class lingulabLiveWebserviceClient_Exception extends Exception
{}

/**
 * LinguLab Web service Client class
 *
 * implementation of LinguLab Web Service
 */
class lingulabLiveWebserviceClient
{	
	/*
	 * magic numbers
	 */	
	private $_loginError = array
	(
		101 => 'Authentication failed',
		102 => 'Authentication session has already opened. You cannot open new session till old is not expired',
		103 => 'Too many login tries. Login tries limit was reached',
		300 => 'Internal error'
	);

    private $_authenticationError = array
	(
		111 => 'Authentication key is not valid',
		112 => 'Authentication key was expired',
		210 => 'Wrong parameter. Empty authentication key',
		310 => 'Internal error'
	);
	
	/**
	 * Authentication Key local store
	 * 
	 * @var string
	 */
	private $_authKey = '';
	
	/**
	 * config local store
	 * 
	 * @var lingulabLiveOptions
	 */
	private $_config = null;
	
	/**
	 * service local store
	 * 
	 * @var lingulabLiveWebservice
	 */
	private $_service = null;
	
	/**
	 * constructor
	 */
	public function __construct()
	{
		require_once dirname(__FILE__) . '/webservice.php';
		require_once dirname(__FILE__) . '/options.php';
		
		$this->_service = new lingulabLiveWebservice();
				
		$this->_config = lingulabLiveOptions::my();
	}
	
	/**
	 * AuthKey Getter
	 * 
	 * Will always return a useable authentication key
	 * given that the webservice is properly working
	 * and provided username and password are valid.
	 * 
	 * @return string
	 */
	protected function getAuthKey()
	{	
		$authKey = $this->_authKey;
		
		if ( empty($authKey) )
			$authKey = $this->_config->authKey;
			
		if ( empty($authKey) )
			$authKey = $this->login();
			
		// if authKey is empty preconditions are not met
		if ( empty($authKey) )
			throw new lingulabLiveWebserviceClient_Exception('Invalid Credentials or Webservice is down.', 10001);
			
		if ( !$this->isUseable($authKey) )
		{
			$authKey = $this->login();
			
			if ( !$this->isUseable($authKey) )
				throw new lingulabLiveWebserviceClient_Exception('Unable to refresh authentication. Invalid Credentials or Webservice is down.', 10002);
		}
		
		return $authKey;
	}
	
	/**
	 * 
	 * @param string $authKey
	 * @return void
	 */
	protected function setAuthKey($authKey)
	{
		$this->_config->authKey = $authKey;
		$this->_authKey = $authKey;
	}
	
	/**
	 * configurations getter
	 * 
	 * get a list of available configurations
	 * 
	 * @throws lingulabLiveWebserviceClient_Exception in case of error 
	 * @return array configurations
	 */
	public function getConfigurations()
	{
		$authKey = $this->getAuthKey();
		$result = $this->_service->GetConfigurations($authKey);
		$code   = $result['ErrorCode'];
		
		if ( 0 != $code )
		// we have an error
		{
			throw new lingulabLiveWebserviceClient_Exception(sprintf('Error while getting configurations: #%d - %s', $code, $result['ErrorMessage']), 12000 + $code);
		}
		
		// normalize configurations (because of nusoap style?!)
		$data = $result['Configurations']['ConfigurationEntry'];
		$configs = array();
		
		if ( isset($data['Id']) )
		// single entry
		{
			$configs[] = $data;
		} 
		else
		// multiple entries 
		{
			$configs = $data;
		}
		
		return $configs;
	}
	
	public function getLanguages(){
		$authKey = $this->getAuthKey();
		$result = $this->_service->GetLanguages($authKey);
		$code   = $result['ErrorCode'];
		
		if ( 0 != $code )
		// we have an error
		{
			throw new lingulabLiveWebserviceClient_Exception(sprintf('Error while getting language data: #%d - %s', $code, $result['ErrorMessage']), 12000 + $code);
		}
		
		// normalize configurations (because of nusoap style?!)
		$data = $result['Languages']['LanguageEntry'];
		$langs = array();
		
		if ( isset($data['LanguageKey']) )
		// single entry
		{
			$langs[] = $data;
		} 
		else
		// multiple entries 
		{
			$langs = $data;
		}
		
		return $langs;
	}
	

	/**
	 * login
	 * 
	 * log into webservice.
	 * 
	 * @return string authKey
	 */
	protected function login()
	{
		$user = $this->_config->user;
		$pass = $this->_config->pass;
		
		$result = $this->_service->Login($user, $pass);
		$code   = $result['ErrorCode'];		
		
		if ( 0 == $code )
		// login success
        {
        	$authKey = $result['AuthenticationKey'];
        	$this->setAuthKey($authKey);
        }
        else
        // login failed
        {	 
			switch( $code )
			{
				case 101: // Authentication failed					
				case 103: // Too many login tries. Login tries limit was reached
				case 300: // Internal error
					throw new lingulabLiveWebserviceClient_Exception(sprintf('Fatal Error while logging in: #%d - %s', $code, $_loginError[$code]), 11000 + $code);
					
				case 102: // Authentication session has already opened. You cannot open new session till old is not expired
					$authKey = $this->_config->authKey;
					break;
					
				default:
					throw new lingulabLiveWebserviceClient_Exception(sprintf('Unhandeled Code while logging in: #%d - %s', $code, $_loginError[$code]), 11000 + $code);					
			}
        }

        return $authKey;
	}
	
	/**
	 * 
	 * @param array $inputData
	 * @return array result
	 */
	public function processText(array $inputData)
	{		
		$service = $this->_service;
		
		$authKey = $this->getAuthKey();
		
		return $service->ProcessText($inputData, $authKey);		
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getUpdatedText($resultKey)
	{
		$service = $this->_service;
		
		$authKey = $this->getAuthKey();
				
		return $service->GetUpdatedText($resultKey, $authKey);
		
	}

	/**
	 * getLoginResult
	 * 
	 * wether or not it is possible to login
	 * with this username and password.
	 * 
	 * 
	 * 
	 * @param string $user
	 * @param string $pass
	 * @return int login errorcode, 0: OK, !0: Error 
	 */
	public function getLoginResult($user, $pass, &$authkey)
	{
		$result = $this->_service->Login($user, $pass);
		$code   = $result['ErrorCode'];
				
		if (isset($result['AuthenticationKey']))
		{
			$authkey = $result['AuthenticationKey'];
			$this->setAuthKey($authkey);
		}
		
		return $code;
	}
	
	/**
	 * isUseable
	 * 
	 * wether or not an authentication key
	 * can be actually used.
	 * 
	 * will return false on transport errors as well.
	 * 
	 * @param string $authKey
	 * @return boolean true if the key can be used, false if not
	 */
	public function isUseable($authKey)
	{
		$service = $this->_service;
		
		$result = $service->ValidateAuthentication($authKey);

		return (bool) (0 == $result);
	}

} // class