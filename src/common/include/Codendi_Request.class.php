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

class Codendi_Request {
    /**
     * @var array
     * @access protected
     */
    protected $_validated_input;

    /**
     * @var array
     * @access protected
     */
    protected $_last_access_to_input;

    /**
     * @var array
     */
    public $params;

    /**
     * @var UserManager
     */
    protected $current_user;

    /**
     * @var Project
     */
    protected $project;

    /** @var ProjectManager */
    private $project_manager;

    /**
     * Constructor
     */
    public function __construct($params, ProjectManager $project_manager = null) {
        $this->params                = $params;
        $this->_validated_input      = array();
        $this->_last_access_to_input = array();
        $this->project_manager       = $project_manager ? $project_manager : ProjectManager::instance();
    }

    public function registerShutdownFunction() {
        if (ForgeConfig::get('DEBUG_MODE') && (strpos($_SERVER['REQUEST_URI'], '/soap/') !== 0)) {
            $php_code = '$request =& '. get_class($this) .'::instance(); $request->checkThatAllVariablesAreValidated();';
            register_shutdown_function(create_function('', $php_code));
        }
    }

    public function getCookie($name) {
        $cookie_manager = new CookieManager();
        return $cookie_manager->getCookie($name);
    }

    public function isCookie($name) {
        $cookie_manager = new CookieManager();
        return $cookie_manager->isCookie($name);
    }

    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
    }

    /**
     * Returns from where the variable is accessed.
     *
     * @return string
     */
    protected function _getCallTrace() {
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
    public function get($variable) {
        $this->_last_access_to_input[$variable] = $this->_getCallTrace();
        return $this->_get($variable, $this->params);
    }

    /**
     * Add a param and/or set its value
     *
     */
    public function set($name, $value) {
        $this->params[$name] = $value;
    }

    /**
     * Get value of $idx[$variable] user submitted values.
     *
     * For instance if you have:
     *   user_preference[103] => "awesome"
     * You gets "awesome" with
     *   getInArray('user_preference', 103);
     *
     * @param String $idx The index of the variable array in $this->params.
     * @param String Name of the parameter to get.
     *
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    public function getInArray($idx, $variable) {
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
     * @access protected
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
    public function exist($variable) {
        return $this->_exist($variable, $this->params);
    }

    /**
     * Check if $variable exists in $array.
     *
     * @access protected
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    protected function _exist($variable, $array) {
        return isset($array[$variable]);
    }

    /**
     * Check if $variable exists and is not empty in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return boolean
     */
    public function existAndNonEmpty($variable) {
        return ($this->exist($variable) && trim($this->params[$variable]) != '');
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param Valid  Validator to apply
     * @return boolean
     */
    public function valid(&$validator) {
        $this->_validated_input[$validator->getKey()] = true;
        return $validator->validate($this->get($validator->getKey()));
    }

    /**
     * Apply validator on all values of a submitted user array.
     *
     * @param Valid  Validator to apply
     * @return boolean
     */
    public function validArray(&$validator) {
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
    public function validInArray($index, &$validator) {
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
    public function validKey($key, &$rule) {
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
    public function getValidated($variable, $validator = 'string', $default_value = null) {
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
    public function checkThatAllVariablesAreValidated() {
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
     * Return the authenticated current user if any (null otherwise)
     *
     * @return PFUser
     */
    public function getCurrentUser() {
        if (!$this->current_user) {
            $this->current_user = UserManager::instance()->getCurrentUser();
        }
        return $this->current_user;
    }

    public function checkUserIsSuperUser()
    {
        if (! $this->getCurrentUser()->isSuperUser()) {
            exit_error(
                $GLOBALS['Language']->getText('include_session', 'insufficient_access'),
                $GLOBALS['Language']->getText('include_session', 'no_access')
            );
        }
    }

    /**
     * Set a current user (should be used only for tests)
     *
     * @param PFUser $user
     */
    public function setCurrentUser(PFUser $user) {
        $this->current_user = $user;
    }

    /**
     * Return the requested project (url parameter: group_id)
     *
     * @return Project
     */
    public function getProject() {
        return $this->project_manager->getProject((int)$this->get('group_id'));
    }

    /**
     * For debug only
     */
    public function dump() {
        var_dump($this->params);
    }

    /**
     * Return the content of the request when posted as JSon
     *
     * @see http://stackoverflow.com/questions/3063787/handle-json-request-in-php
     */
    public function getJsonDecodedBody() {
        return json_decode(file_get_contents('php://input'));
    }

    /**
     * @return integer
     */
    public function getTime() {
        return $_SERVER['REQUEST_TIME'];
    }
}
?>
