<?php
/**
 * LinguLab WordPress Plugin
 * 
 * Copyright 2009 Tom Klingenberg <lastflood.com>
 *
 * @author Tom Klingenberg
 */


/**
 * lingulabLiveWebservice_Exception class
 */
class lingulabLiveWebservice_Exception extends Exception
{}

/**
 * LinguLab webservice class
 *
 * LinguLab webservice php implementation (proxy/model)
 */
class lingulabLiveWebservice
{
	/*
	 * magic numbers
	 */
	private $_serviceUrl  = 'https://api.lingulab.net/LiveService.asmx?wsdl'; # URI of webservice

	/**
	 * soap client local instance
	 * 
	 * @var soap_client
	 */
	private $_client = null;	
	
	/**
	 * standard soap call incl. error handling and exception 
	 * throwing in form of a local helper function to 
	 * reduce duplicate code.
	 * 
	 * @param string $operation remote procedure
	 * @param array $params parameters
	 * @return array response
	 */
	private function _stdCall($operation, $params = array())
	{
		$client = $this->getSoapClient();
		
		$result = $client->call($operation, $params);		
		$error  = $client->getError();
				
		if ($client->fault)
		{
			$message = sprintf('Error has occured during remote call. Operation: %s.', $operation);
			// commented more informative message 
			// $message = sprintf('Error has occured during remote call. Operation: %s, Params: %s, Details: %s', $operation, print_r($params, true), $client->faultdetail)
			throw new lingulabLiveWebservice_Exception($message, 10001);
		} 
		else if ($error) 
		{
			throw new lingulabLiveWebservice_Exception(sprintf('Constructor error: %s', $error) , 10002);
		}		
		return $result; 		
	}

	/**
	 * constructor
	 */
	public function __construct()
	{
		require_once 'nusoap.php';
		$serviceURL = $this->_serviceUrl;
		$this->_client = new soap_client($serviceURL, true);
		/*  configure nusoap */
			
		// default encoding for autgoing messages to UTF-8 			
		// $this->_client->soap_defencoding = 'UTF-8';
		// resp. Default Blog Charset Encoding		
		$client->soap_defencoding = get_bloginfo('charset');	
	}
	
	/**
	 * Soap Client Getter
	 * 
	 * @return soap_client
	 */
	public function getSoapClient()
	{
		return $this->_client;
	}
	
	/**
	 * GetConfigurations  
	 * 
	 * @param string $authKey
	 * @return array webservice result
	 */
	public function GetConfigurations($authKey, $lang = "de")
	{
		$result = $this->_stdCall('GetConfigurations', array(array('authenticationKey' => $authKey, 'languageKey' => $lang)));
		return $result['GetConfigurationsResult'];		
	}

	public function GetLanguages($authKey)
	{
		$result = $this->_stdCall('GetLanguages', array(array('authenticationKey' => $authKey)));
		return $result['GetLanguagesResult'];		
	}
	
	/**
	 * GetUpdatedText  
	 * 
	 * @param string $authKey
	 * @return array webservice result
	 */
	public function GetUpdatedText($resultKey, $authKey)
	{
		$result = $this->_stdCall('GetUpdatedText', array(array('resultKey' => $resultKey, 'authenticationKey' => $authKey)));
		return $result['GetUpdatedTextResult'];		
	}
	
	/**
	 * login
	 * 
	 * authenticate against webservice
	 * 
	 * @param string $user Username
	 * @param string $pass Password
	 * @return array webservice LoginResult
	 */
	public function Login($user, $pass)
	{
		$result = $this->_stdCall('Login', array(array('userName' => $user, 'password' => $pass, 'plugInId' => '08d81e0c-9f6d-488c-b34d-447b40797561')));
		return $result['LoginResult'];
	}
	
	/**
	 * process text
	 * 
	 * @param array $inputData
	 * @param string $authKey
	 * @return array webservice ProcessTextResult
	 */
	public function ProcessText($inputData, $authKey)
	{
		$result = $this->_stdCall('ProcessText', array(array('inputData' => $inputData, 'authenticationKey' => $authKey))); 
		return $result['ProcessTextResult'];
	} 
	
	/**
	 * validate authentication
	 * 
	 * validate authentication-key against webservice
	 * 
	 * @param string $authKey
	 * @return int ValidateAuthenticationResult
	 */
	public function ValidateAuthentication($authKey)
	{
		$result = $this->_stdCall('ValidateAuthentication', array(array('authenticationKey' => $authKey))); 
		return $result['ValidateAuthenticationResult'];
	}
	
} // class
