<?php
/**
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 *
 */

/**
 * CodeXSOAP - Wrapper function for NuSOAP class.
 *
 * This class will pass on each command common variables to the server, like the
 * session ID and the project name
 */
class CodeXSOAP extends soap_client {
	var $sess_hash;
	var $wsdl_string;
	var $connected;
	var $session_string;
	var $session_file;		// Configuration file for this session
	var $session_group_id;	// Default group
	var $session_user;		// Logged user name
    var $session_user_id;	// Logged user ID
	
	/**
	 * constructor
	 */
	function CodeXSOAP() {
		$this->wsdl_string = "";
		$this->connected = false;
		$this->session_string = "";
		$this->session_group_id = 0;		// By default don't use a group
		$this->session_user = "";
        $this->session_user_id = 0;
		
		// Try to find a dir where to put the session file
		if (array_key_exists("HOME", $_ENV)) {
			$session_dir = $_ENV["HOME"]."/";
		} else if (array_key_exists("HOMEPATH", $_ENV) && array_key_exists("HOMEDRIVE", $_ENV)) {		// For Windows
			$session_dir = $_ENV["HOMEDRIVE"]."\\".$_ENV["HOMEPATH"]."\\";
		}
		$this->session_file = $session_dir.".codexrc";
		$this->readSession();
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
			if (!array_key_exists("sessionKey", $params)) $params["sessionKey"] = $this->session_string;
		}
		$LOG->add("CodeXSOAP::Executing command ".$command."...");
        return parent::call($command,$params);
	}
	
	/**
	 * connect - Establish the connection to the server. This is done in the constructor
	 * of the soap_client class
	 */
	function connect() {
		global $LOG;
		
		if (!$this->wsdl_string) {
			if (defined("WSDL_URL")) {
				$this->wsdl_string = WSDL_URL;
			} else {
				exit_error("CodeXSOAP: URL of the WSDL is not defined. Please set your CODEX_WSDL environment variable.");
			}
		}
		
		$LOG->add("CodeXSOAP::Connecting to the server ".$this->wsdl_string."...");
		parent::soap_client($this->wsdl_string, "wsdl");
		if (($error = $this->getError())) {
			exit_error($error, $this->faultcode);
		}
		$LOG->add("CodeXSOAP::Connected!");
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
    
	function setWSDL($wsdl) {
		$this->wsdl_string = $wsdl;
	}
	
	function saveSession() {
		$handler = fopen($this->session_file, "w");
		if (!$handler) {
			exit_error("Could not open session file ".$this->session_file." for writing");
		}
		
		fputs($handler, "wsdl_string=\"".$this->wsdl_string."\"\n");
		fputs($handler, "session_string=\"".$this->session_string."\"\n");
		fputs($handler, "session_group_id=\"".$this->session_group_id."\"\n");
		fputs($handler, "session_user=\"".$this->session_user."\"\n");
        fputs($handler, "session_user_id=\"".$this->session_user_id."\"\n");
		fclose($handler);
		
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
?>
