<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * CookieManager
 * 
 * Manages cookies
 */
class CookieManager {
    
    function setCookie($name, $value, $expire = 0) {
        // Make sure there isn't a port number in the default domain name
        // or the setcookie for the entire domain won't work
        if (isset($GLOBALS['sys_cookie_domain'])) {
            $expl = explode(':',$GLOBALS['sys_cookie_domain']);
            $host = $expl[0];
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
        $secure = (bool)Config::get('sys_force_ssl');
        if($this->isPhpHttpOnlyCompatible() == true) {
            $httpOnly = true;
            return setcookie($this->getInternalCookieName($name), $value, $expire, '/', $cookie_host, $secure, $httpOnly);
        } else { //This is a workaround to enable HttpOnly on cookie
            return setcookie($this->getInternalCookieName($name), $value, $expire, '/', $cookie_host. '; HttpOnly', $secure);
        }
    }
    
    function getCookie($name) {
        if($this->isCookie($name)) {
            return $_COOKIE[$this->getInternalCookieName($name)];
        } else {
            return '';
        }
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

    /**
     * Check if PHP version support HttpOnly option.
     *
     * HttpOnly is an additional flag included in Cookie.
     * Using the HttpOnly flag when generating a cookie helps mitigate the risk of client side script accessing the protected cookie.
     */
    function isPhpHttpOnlyCompatible() {
        return isPhpVersionEqualOrGreaterTo53();
    }
}
?>
