<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * 
 *
 * HTTPRequest
 * @package CodeX
 */

require_once('browser.php');
require_once('common/include/CodeX_Request.class.php');
require_once('common/valid/Valid.class.php');

/**
 * @package CodeX
 */
class HTTPRequest extends CodeX_Request {
    /**
     * @var array
     * @access private
     */
    var $clean;

    function HTTPRequest() {
    }

    /**
     * Get the value of $variable in $_REQUEST (user submitted values).
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    function get($variable) {
        return $this->_get($variable, $_REQUEST);
    }

    /**
     * Get the value of $variable in $_SERVER (server side values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    function getFromServer($variable) {
        return $this->_get($variable, $_SERVER);
    }

    /**
     * Check if $variable exists in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function exist($variable) {
        return $this->_exist($variable, $_REQUEST);
    }

    /**
     * Check if $variable exists and is not empty in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function existAndNonEmpty($variable) {
        return ($this->exist($variable) && trim($_REQUEST[$variable]) != '');
    }

    /**
     * Check if current request is send via 'post' method.
     *
     * This method is useful to test if the current request comes from a form.
     *
     * @return boolean
     */
    function isPost() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param Valid  Validator to apply
     * @return boolean
     */
    function valid(&$validator) {
        $GLOBALS['validated_input'][$validator->getKey()] = true;
        return $validator->validate($this->get($validator->getKey()));
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param string Variable name
     * @param Rule  Validator to apply
     * @return boolean
     */
    function validKey($key, &$rule) {
        $GLOBALS['validated_input'][$key] = true;
        return $rule->isValid($this->get($key));
    }

    /**
     * Return true if browser used to submit the request is netscape 4.
     *
     * @return boolean
     */
    function browserIsNetscape4() {
        return browser_is_netscape4();
    }

    /**
     * For debug only
     */
    function dump() {
        var_dump($_REQUEST);
    }

    /**
     * Singleton method for the class.
     *
     * @return mixed HTTPRequest Object.
     */
    function &instance() {
        static $_httprequest_instance;
        if (!$_httprequest_instance) {
            $_httprequest_instance = new HTTPRequest();
        }
        return $_httprequest_instance;
    }

    /**
     * Remove slashes in $value. If $value is an array, remove slashes for each
     * element.
     *
     * @access private
     * @param mixed $value
     * @return mixed
     */
    function _stripslashes($value) {
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
            return (get_magic_quotes_gpc()?$this->_stripslashes($array[$variable]):$array[$variable]);
        } else {
            return false;
        }
    }

    /**
     * Check if $variable exists in $array.
     *
     * @access private
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function _exist($variable, $array) {
        return array_key_exists($variable, $array);
    }
}

?>
