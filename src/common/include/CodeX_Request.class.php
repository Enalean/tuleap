<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeX_Request
 */


/* abstract */ class CodeX_Request {
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
    function CodeX_Request($params) {
        $this->params                = $params;
        $this->_validated_input      = array();
        $this->_last_access_to_input = array();
        register_shutdown_function(create_function('', '$request =& '. get_class($this) .'::instance(); $request->checkThatAllVariablesAreValidated();'));
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
        $files = explode('/', $backtrace[0]['file']);
        return $files[count($files) - 4] . '/'.
            $files[count($files) - 3] . '/'.
            $files[count($files) - 2] . '/'.
            $files[count($files) - 1] . ' Line: '.
            $backtrace[0]['line'];
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
