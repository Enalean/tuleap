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
            $host = $this->getHostNameWithoutPort($GLOBALS['sys_cookie_domain']);
        } else {
            $host = $this->getHostNameWithoutPort($GLOBALS['sys_default_domain']);
        }

        if ($this->isIpAdress($host) || $this->isHostWithoutTLD($host)) {
            $cookie_host = '';
        } else {
            $cookie_host = ".".$host;
        }
        $secure = (bool)Config::get('sys_force_ssl');
        return $this->phpsetcookie($this->getInternalCookieName($name), $value, $expire, '/', $cookie_host, $secure);
    }

    private function getHostNameWithoutPort($domain) {
        if (strpos($domain, ':') !== false) {
            list($host,) = explode(':', $domain);
            return $host;
        }
        return $domain;
    }

    private function isIpAdress($host) {
        return preg_match('/[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+/', $host);
    }

    private function isHostWithoutTLD($host) {
        return strpos($host, ".") === false;
    }

    protected function phpsetcookie($name, $value, $expire, $path, $domain, $secure) {
        if($this->isPhpHttpOnlyCompatible() == true) {
            return setcookie($name, $value, $expire, $path, $domain, $secure, true);
        } else { //This is a workaround to enable HttpOnly on cookie
            return setcookie($name, $value, $expire, $path, $domain. '; HttpOnly', $secure);
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
        return server_is_php_version_equal_or_greater_than_53();
    }
}
?>
