<?php
/**
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 *
 */

/**
 * CodendiSOAP - Wrapper function for SOAP class.
 *
 * This class will pass on each command common variables to the server, like the
 * session ID and the project name
 */
class CodendiSOAP extends SoapClient {
	var $sess_hash;
	var $wsdl_string;
	var $proxy_host;
	var $proxy_port;
	var $connected;
	var $session_string;
	var $session_file;		// Configuration file for this session
	var $session_group_id;	// Default group
	var $session_user;		// Logged user name
    var $session_user_id;	// Logged user ID
    protected $fileChunkSize;
    protected $maxRetry;  // Max number of soap call retry in case of failure
    protected $callDelay; // Time "spacer" between 2 failing soap calls
	/**
	 * constructor
	 */
	function __construct() {
		$this->wsdl_string = "";
		$this->proxy_host = "";
		$this->proxy_port = 0;
		$this->connected = false;
		$this->session_string = "";
		$this->session_group_id = 0;		// By default don't use a group
		$this->session_user = "";
        $this->session_user_id = 0;
		$this->fileChunkSize = 6000000; // ~6 Mo;
		$this->maxRetry      = 0;
		$this->callDelay     = 5;

		// Try to find a dir where to put the session file
		$session_dir = 0;
		if (array_key_exists("HOME", $_ENV)) {
			$session_dir = $_ENV["HOME"]."/";
		} else if (array_key_exists("HOMEPATH", $_ENV) && array_key_exists("HOMEDRIVE", $_ENV)) {		// For Windows
			$session_dir = $_ENV["HOMEDRIVE"]."\\".$_ENV["HOMEPATH"]."\\";

		}

		$this->session_file = $session_dir.".codendirc";
		if (file_exists($this->session_file)) {
 			$this->readSession();
		}
	}

	/**
	 * call - Calls a SOAP method
	 *
	 * @param string	Command name
	 * @param array	Parameter array
	 * @param bool		Specify if we should pass the server common parameters like the session ID
	 */
	function call($command,$params=array(),$use_extra_params=true) {
		global $LOG;

		// checks if a session is established
		if ($command != "login" && strlen($this->session_string) == 0) {
			exit_error("You must start a session first using the \"login\" function");
		}

		if (!$this->connected) {		// try to connect to the server
			$this->connect();
		}

		// Add session parameters
		if ($use_extra_params) {
			if (!array_key_exists("sessionKey", $params)) {
                //$params["sessionKey"] = $this->session_string;
                $params = array('sessionKey' => $this->session_string) + $params;   // params need to be in the right order (sessionKey first)
            }
		}

		$nbAttempt       = 0;
		$soapCallSuccess = false;
		do {
			$nbAttempt++;
			try {
				$LOG->add("CodendiSOAP::Executing command ".$command." ...");
				return call_user_func_array(array($this, $command), $params);
			}
			catch (SoapFault $e) {
				if (strtolower($e->getCode()) == 'http' &&
					strtolower($e->getMessage()) == 'error fetching http headers' &&
					$nbAttempt < $this->getMaxRetry()) {
					$GLOBALS['LOG']->add('CodendiSOAP::An error occured while executing '.$command.', try again [Nb attempt: '.$nbAttempt.'/'.$GLOBALS['soap']->getMaxRetry().']. Wait for '.($nbAttempt * $this->getCallDelay()).' seconds (mitigate network congestion) ...');
					sleep($nbAttempt * $this->getCallDelay());
				} else {
					throw $e;
				}
			}
		} while ($nbAttempt < $this->getMaxRetry());
	}

	/**
	 * connect - Establish the connection to the server. This is done in the constructor
	 * of the soap_client class
	 */
	function connect() {
		global $LOG;

        try {
        	$log_proxy = '';
        	if ($this->proxy_host && $this->proxy_port) {
        		$log_proxy = ', using proxy '.$this->proxy_host.':'.$this->proxy_port;
        	}
            $LOG->add("CodendiSOAP::Connecting to the server ".$this->getWSDLString().$log_proxy."...");
            $options = array('trace' => true);
            if ($this->proxy_host && $this->proxy_port) {
            	$options['proxy_host'] = $this->proxy_host;
            	$options['proxy_port'] = (int)$this->proxy_port;
            }
            parent::__construct($this->getWSDLString(), $options);
        } catch (SoapFault $fault) {
            exit_error($fault, $this->faultcode);
		}
		$LOG->add("CodendiSOAP::Connected!");
		$this->connected = true;

	}

	/**
	 * setSessionString - Set the session ID for future calls
	 *
	 * @param string Session string ID
	 */
	function setSessionString($string) {
		$this->session_string = $string;
	}

	function setSessionGroupID($group_id) {
		$this->session_group_id = $group_id;
	}

	function getSessionGroupID() {
		return $this->session_group_id;
	}

	function setSessionUser($user) {
		$this->session_user = $user;
	}

	function getSessionUser() {
		return $this->session_user;
	}

    function setSessionUserID($user_id) {
		$this->session_user_id = $user_id;
	}

	function getSessionUserID() {
		return $this->session_user_id;
	}

	function setWSDLString($wsdl) {
		$this->wsdl_string = $wsdl;
	}
    function getWSDLString() {
		if (!$this->wsdl_string) {
			if (defined("WSDL_URL")) {
				$this->wsdl_string = WSDL_URL;
			} else {
				exit_error("SOAP API: URL of the WSDL is not defined. Please set your TULEAP_WSDL environment variable.");
			}
		}
        return $this->wsdl_string;
    }

	function setProxy($proxy) {
		$arr_proxy = explode(":", $proxy);
		$this->proxy_host = $arr_proxy[0];
		$this->proxy_port = $arr_proxy[1];
	}
	function getProxyHost() {
		return $this->proxy_host;
	}
	function getProxyPort() {
		return $this->proxy_port;
	}

	function getFileChunkSize() {
		return $this->fileChunkSize;
	}
	function setFileChunkSize($size) {
		$this->fileChunkSize = $size;
	}

	function getMaxRetry() {
		return $this->maxRetry;
	}
	function setMaxRetry($maxRetry) {
		$this->maxRetry = $maxRetry;
	}

	function getCallDelay() {
		return $this->callDelay;
	}
	function setCallDelay($callDelay) {
		$this->callDelay = $callDelay;
	}

	function saveSession() {
		// If file doesn't exist, create first and set the right permissions
		if (!file_exists($this->session_file)) {
			touch($this->session_file);
			chmod($this->session_file, 0600);
		}

		$content = '';
		$content .= "wsdl_string=\"".$this->getWSDLString()."\"".PHP_EOL;
		$content .= "session_string=\"".$this->session_string."\"".PHP_EOL;
		$content .= "session_group_id=\"".$this->session_group_id."\"".PHP_EOL;
		$content .= "session_user=\"".$this->session_user."\"".PHP_EOL;
		$content .= "session_user_id=\"".$this->session_user_id."\"".PHP_EOL;
		$content .= "proxy_host=\"".$this->proxy_host."\"".PHP_EOL;
		$content .= "proxy_port=\"".$this->proxy_port."\"".PHP_EOL;
		$content .= "file_chunk_size=\"".$this->fileChunkSize."\"".PHP_EOL;

		if (!file_put_contents($this->session_file, $content)) {
			exit_error("Could not open session file ".$this->session_file." for writing");
		}
		chmod($this->session_file, 0600);
	}

	function readSession() {
		// Read session info (if exists)
		if (file_exists($this->session_file)) {
			$session = parse_ini_file($this->session_file, false);
			if (array_key_exists("session_string", $session)) {
				$this->session_string = $session["session_string"];
				$this->session_group_id = $session["session_group_id"];
				$this->session_user = $session["session_user"];
                $this->session_user_id = $session["session_user_id"];
				$this->wsdl_string = $session["wsdl_string"];
				$this->proxy_host = $session["proxy_host"];
				$this->proxy_port = $session["proxy_port"];
				if (isset($session["file_chunk_size"])) {
				    $this->fileChunkSize = $session["file_chunk_size"];
				}
			}
		}
	}

	function endSession() {
		if (file_exists($this->session_file) && !@unlink($this->session_file)) {
			exit_error("Could not delete existing session file ".$this->session_file);
		}

		$this->session_group_id = 0;
		$this->session_string = "";
		$this->session_user = "";
        $this->session_user_id = 0;
	}
}
