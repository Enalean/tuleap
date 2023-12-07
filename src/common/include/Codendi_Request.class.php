<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Project\ProjectByIDFactory;

class Codendi_Request
{
    /**
     * @var array
     */
    public $params;

    /**
     * @var UserManager
     */
    protected $current_user;

    protected Project|null $project = null;

    private readonly ProjectByIDFactory $project_manager;

    /**
     * Constructor
     */
    public function __construct($params, ?ProjectByIDFactory $project_manager = null)
    {
        $this->params          = $params;
        $this->project_manager = $project_manager ?: ProjectManager::instance();
    }

    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) === 'XMLHTTPREQUEST';
    }

    /**
     * Get the value of $variable in $this->params (user submitted values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     *
     * @psalm-taint-source input
     */
    public function get($variable)
    {
        return $this->_get($variable, $this->params);
    }

    /**
     * Add a param and/or set its value
     *
     */
    public function set($name, $value)
    {
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
     *
     * @psalm-taint-source input
     */
    public function getInArray($idx, $variable)
    {
        if (is_array($this->params[$idx])) {
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
     *
     * @psalm-taint-source input
     */
    public function _get($variable, $array)
    {
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
     * @return bool
     */
    public function exist($variable)
    {
        return $this->_exist($variable, $this->params);
    }

    /**
     * Check if $variable exists in $array.
     *
     * @access protected
     * @param string $variable Name of the parameter.
     * @return bool
     */
    protected function _exist($variable, $array)
    {
        return isset($array[$variable]);
    }

    /**
     * Check if $variable exists and is not empty in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     * @return bool
     */
    public function existAndNonEmpty($variable)
    {
        return ($this->exist($variable) && trim($this->params[$variable]) != '');
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param Valid  Validator to apply
     * @return bool
     */
    public function valid($validator)
    {
        return $validator->validate($this->get($validator->getKey()));
    }

    /**
     * Apply validator on all values of a submitted user array.
     *
     * @param Valid  Validator to apply
     * @return bool
     */
    public function validArray($validator)
    {
        $isValid = true;
        $array   = $this->get($validator->getKey());
        if (is_array($array)) {
            if (count($array) > 0) {
                foreach ($array as $key => $v) {
                    if (! $validator->validate($v)) {
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
     * @return bool
     */
    public function validInArray($index, $validator)
    {
        return $validator->validate($this->getInArray($index, $validator->getKey()));
    }

    /**
     * Apply validator on submitted user value.
     *
     * @param string Variable name
     * @param Rule  Validator to apply
     * @return bool
     */
    public function validKey($key, $rule)
    {
        return $rule->isValid($this->get($key));
    }

    /**
     * Apply validator on submitted user value and return the value if valid
     * Else return default value
     * @param string $variable Name of the parameter to get.
     * @param mixed $validator Name of the validator (string, uint, email) or an instance of a validator
     * @param mixed $default_value Value return if the validator is not valid. Optional, default is null.
     *
     * @psalm-taint-source input
     */
    public function getValidated($variable, $validator = 'string', $default_value = null)
    {
        $is_valid = false;
        if ($v = ValidFactory::getInstance($validator, $variable)) {
            $is_valid = $this->valid($v);
        } else {
            trigger_error('Validator ' . $validator . ' is not found', E_USER_ERROR);
        }
        return $is_valid ? $this->get($variable) : $default_value;
    }

    public function getToggleVariable($variable)
    {
        if ($this->exist($variable) && (int) $this->get($variable) === 1) {
            return 1;
        }
        return 0;
    }

    /**
     * Return the authenticated current user if any (null otherwise)
     *
     * @return PFUser
     */
    public function getCurrentUser()
    {
        if (! $this->current_user) {
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
     */
    public function setCurrentUser(PFUser $user)
    {
        $this->current_user = $user;
    }

    /**
     * Return the requested project (url parameter: group_id)
     */
    public function getProject(): Project
    {
        $requested_project_id = (int) $this->get('group_id');
        if ($this->project === null || ($requested_project_id && $requested_project_id !== (int) $this->project->getID())) {
            $this->project = $this->project_manager->getProjectById($requested_project_id);
        }

        return $this->project;
    }

    /**
     * @psalm-internal \Tuleap\Test\Builders
     */
    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Return the content of the request when posted as JSon
     *
     * @see http://stackoverflow.com/questions/3063787/handle-json-request-in-php
     */
    public function getJsonDecodedBody()
    {
        return json_decode(file_get_contents('php://input'));
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $_SERVER['REQUEST_TIME'];
    }
}
