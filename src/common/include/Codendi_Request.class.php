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

/* abstract */ class Codendi_Request {
    /**
     * @var array
     * @access private
     */
    var $_validated_input;
    
    /**
     * @var array
     * @access private
     */
    var $_last_access_to_input;
    
    /**
     * @var array
     */
    var $params;
    
    /**
     * Constructor
     */
    function Codendi_Request($params) {
        $this->params                = $params;
        $this->_validated_input      = array();
        $this->_last_access_to_input = array();
    }
    
    function registerShutdownFunction() {
        if (Config::get('DEBUG_MODE') && (strpos($_SERVER['REQUEST_URI'], '/soap/') !== 0)) {
            register_shutdown_function(create_function('', '$request =& '. get_class($this) .'::instance(); $request->checkThatAllVariablesAreValidated();'));
        }
    }
    
    function getCookie($name) {
        $cookie_manager =& new CookieManager();
        return $cookie_manager->getCookie($name);
    }
    
    function isCookie($name) {
        $cookie_manager =& new CookieManager();
        return $cookie_manager->isCookie($name);
    }
    
    function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
    }

    /**
     * Returns from where the variable is accessed.
     *
     * @return string
     */
    function _getCallTrace() {
        $backtrace = debug_backtrace();
        $files = explode('/', $backtrace[1]['file']);
        return $files[count($files) - 4] . '/'.
            $files[count($files) - 3] . '/'.
            $files[count($files) - 2] . '/'.
            $files[count($files) - 1] . ' Line: '.
            $backtrace[1]['line'];
    }

    /**
     * Get the value of $variable in $this->params (user submitted values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    function get($variable) {
        $this->_last_access_to_input[$variable] = $this->_getCallTrace();
        return $this->_get($variable, $this->params);
    }

    /**
     * Add a param and/or set its value
     *
     */
    function set($name, $value) {
        $this->params[$name] = $value;
    }

    /**
     * Get value of $idx[$variable] in $this->params (user submitted values).
     *
     * @param string The index of the variable array in $this->params.
     * @param string Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    function getInArray($idx, $variable) {
        $this->_last_access_to_input[$idx][$variable] = $this->_getCallTrace();
        if(is_array($this->params[$idx])) {
            return $this->_get($variable, $this->params[$idx]);
        } else {
            return false;
        }
    }

    /**
     * Get the value of $variable in $array. 
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @param array $array Name of the parameter to get.
     */
    function _get($variable, $array) {
        if ($this->_exist($variable, $array)) {
            return $array[$variable];
        } else {
            return false;
        }
    }

    /**
     * Check if $variable exists in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function exist($variable) {
        return $this->_exist($variable, $this->params);
    }
    
    /**
     * Check if $variable exists in $array.
     *
     * @access private
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function _exist($variable, $array) {
        return isset($array[$variable]);
    }
    
    /**
     * Check if $variable exists and is not empty in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    function existAndNonEmpty($variable) {
        return ($this->exist($variable) && trim($this->params[$variable]) != '');
    }
    
    /**
     * Apply validator on submitted user value.
     *
     * @param Valid  Validator to apply
     * @return boolean
     */
    function valid(&$validator) {
        $this->_validated_input[$validator->getKey()] = true;
        return $validator->validate($this->get($validator->getKey()));
    }

    /**
     * Apply validator on all values of a submitted user array.
     *
     * @param Valid  Validator to apply
     * @return boolean
     */
    function validArray(&$validator) {
        $this->_validated_input[$validator->getKey()] = true;
        $isValid = true;
        $array = $this->get($validator->getKey());
        if (is_array($array)) {
            if (count($array)>0) {
                foreach ($array as $key => $v) {
                    if (!$validator->validate($v)) {
                        $isValid = false;
                    }
                }
            } else {
                $isValid = $validator->validate(null); 
            }
        } else {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * Apply validator on submitted user array.
     *
     * @param string Index in the user submitted values where the array stands.
     * @param Valid  Validator to apply
     * @return boolean
     */
    function validInArray($index, &$validator) {
        $this->_validated_input[$index][$validator->getKey()] = true;
        return $validator->validate($this->getInArray($index, $validator->getKey()));
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param string Variable name
     * @param Rule  Validator to apply
     * @return boolean
     */
    function validKey($key, &$rule) {
        $this->_validated_input[$key] = true;
        return $rule->isValid($this->get($key));
    }
    
    /**
     * Apply validator on submitted user value and return the value if valid
     * Else return default value
     * @param string $variable Name of the parameter to get.
     * @param mixed $validator Name of the validator (string, uint, email) or an instance of a validator
     * @param mixed $default_value Value return if the validator is not valid. Optional, default is null.
     */
    function getValidated($variable, $validator = 'string', $default_value = null) {
        $is_valid = false;
        if ($v = ValidFactory::getInstance($validator, $variable)) {
            $is_valid = $this->valid($v);
        } else {
            trigger_error('Validator '. $validator .' is not found', E_USER_ERROR);
        }
        return $is_valid ? $this->get($variable) : $default_value;
    }
    
    /**
     * Check that all submitted value has been validated
     */
    function checkThatAllVariablesAreValidated() {
        foreach($this->params as $key => $v) {
            if(is_array($v)) {
                foreach($v as $subK => $subV) {
                    if(!isset($this->_validated_input[$key][$subK])) {
                        trigger_error('Variable: '.$key.'['.$subK.'] not validated. Last access @ '. (isset($this->_last_access_to_input[$key][$subK]) ? $this->_last_access_to_input[$key][$subK] : 'unknown'), E_USER_NOTICE);
                    }
                }
            } else {
                if(!isset($this->_validated_input[$key])) {
                    trigger_error("Variable: $key not validated. Last access @ ". (isset($this->_last_access_to_input[$key]) ? $this->_last_access_to_input[$key] : 'unknown'), E_USER_NOTICE);
                }
            }
        }
    }
    
    /**
     * For debug only
     */
    function dump() {
        var_dump($this->params);
    }
}
?>
