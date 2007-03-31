<?php
/**
* CookieManager
* 
* Manages cookies
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class CookieManager {
    
    function setCookie($name, $value, $expire = 0) {
        // Make sure there isn't a port number in the default domain name
        // or the setcookie for the entire domain won't work
        if (isset($GLOBALS['sys_cookie_domain'])) {
            $host = $GLOBALS['sys_cookie_domain'];
        } else {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
        }
	// If local machine, don't use a specific cookie host
	$pos=strpos($host,".");
	if ($pos === false) {
	  $cookie_host="";
	} else if (browser_is_netscape4()) {
            $cookie_host=$host;
        } else {
            $cookie_host=".".$host;
        }
        return setcookie($this->getInternalCookieName($name), $value, $expire, '/', $cookie_host);
    }
    
    function getCookie($name) {
        return $_COOKIE[$this->getInternalCookieName($name)];
    }
    
    function isCookie($name) {
        return isset($_COOKIE[$this->getInternalCookieName($name)]);
    }
    
    function removeCookie($name) {
        $this->setCookie($name, '');
    }
    
    function getInternalCookieName($name) {
        return $GLOBALS['sys_cookie_prefix'] .'_'. $name;
    }
}
?>
