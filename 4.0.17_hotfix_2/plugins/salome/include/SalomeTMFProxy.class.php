<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFProxy
 */

class SalomeTMFProxy {
	
	/**
     * @var string $_proxy the url of the proxy, with port if needed
     */
    var $_proxy;
    
    /**
     * @var string $_proxy_user the name of the user for proxy authentification if needed
     */
    var $_proxy_user;
    
    /**
     * @var string $_proxy_password the password of the user for proxy authentification if needed
     */
    var $_proxy_password;
    
    /**
     * @var boolean $_proxy_active true if the proxy is active, false otherwise
     */
    var $_proxy_active;
    
    function SalomeTMFProxy($proxy, $username, $password, $active) {
    	$this->_proxy = $proxy;
    	$this->_proxy_user = $username;
    	$this->_proxy_password = $password;
    	$this->_proxy_active = $active; 	
    }
    
    function getProxy() {
    	return $this->_proxy;
    }
    
	function getProxyUser() {
    	return $this->_proxy_user;
    }
    
	function getProxyPassword() {
    	return $this->_proxy_password;
    }
    
    function isActive() {
    	return $this->_proxy_active;
    }
    
}
?>