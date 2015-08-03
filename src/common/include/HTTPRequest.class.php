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

require_once('browser.php');

/**
 * @package Codendi
 */
class HTTPRequest extends Codendi_Request {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct($_REQUEST);
    }
    

    /**
     * Get the value of $variable in $this->params (server side values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    public function getFromServer($variable) {
        return $this->_get($variable, $_SERVER);
    }

    /**
     * Check if current request is send via 'post' method.
     *
     * This method is useful to test if the current request comes from a form.
     *
     * @return boolean
     */
    public function isPost() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if browser used to submit the request is netscape 4.
     *
     * @return boolean
     */
    public function browserIsNetscape4() {
        return browser_is_netscape4();
    }

    public function getBrowser() {
        $is_deprecated = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false;
        $is_ie8        = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0') !== false;
        $is_ie9        = strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/5') !== false;

        if ($is_deprecated && $is_ie9) {
            return new BrowserIE9CompatibilityModeDeprecated();
        } else if ($is_deprecated) {
            return new BrowserIE7Deprecated($this->getCurrentUser());
        } else if ($is_ie8) {
            return new BrowserIE8();
        }

        return new Browser();
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return HTTPRequest
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * Validate file upload.
     *
     * @param  Valid_File Validator for files.
     * @return Boolean
     */
    public function validFile(&$validator) {
        if(is_a($validator, 'Valid_File')) {
            $this->_validated_input[$validator->getKey()] = true;
            return $validator->validate($_FILES, $validator->getKey());
        } else {
            return false;
        }
    }

    /**
     * Remove slashes in $value. If $value is an array, remove slashes for each
     * element.
     *
     * @access private
     * @param mixed $value
     * @return mixed
     */
    protected function _stripslashes($value) {
        if (is_string($value)) {
            $value = stripslashes($value);
        } else if (is_array($value)) {
            foreach($value as $key => $val) {
                $value[$key] = $this->_stripslashes($val);
            }
        }
        return $value;
    }

    /**
     * Get the value of $variable in $array. If magic_quotes are enabled, the
     * value is escaped.
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @param array $array Name of the parameter to get.
     */
    function _get($variable, $array) {
        if ($this->_exist($variable, $array)) {
            return (get_magic_quotes_gpc() ? $this->_stripslashes($array[$variable]) : $array[$variable]);
        } else {
            return false;
        }
    }

    public function isSSL() {
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on');
    }

    /**
     * Return request IP address
     *
     * When run behind a reverse proxy, REMOTE_ADDR will be the IP address of the
     * reverse proxy, use this method if you want to get the actual ip address
     * of the request without having to deal with reverse-proxy or not.
     *
     * @return String
     */
    public static function getIPAddress() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
