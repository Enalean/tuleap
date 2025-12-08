<?php
/**
 * Copyright (c) Enalean, 2012-Present. All rights reserved
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

namespace Tuleap;

use Project;
use Tuleap\Project\ProjectByIDFactory;
use UserManager;
use ValidFactory;

class HTTPRequest
{
    private(set) array $params;
    private ?\PFUser $current_user = null;

    protected ?Project $project = null;

    private readonly ProjectByIDFactory $project_manager;

    public function __construct(?array $params = null, ?ProjectByIDFactory $project_manager = null)
    {
        $this->params          = $params ?? $_REQUEST;
        $this->project_manager = $project_manager ?? \ProjectManager::instance();
    }

    /**
     * Get the value of $variable in $this->params (server side values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    public function getFromServer($variable)
    {
        return $this->_get($variable, $_SERVER);
    }

    /**
     * Check if current request is send via 'post' method.
     *
     * This method is useful to test if the current request comes from a form.
     */
    public function isPost(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hold an instance of the class
     */
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public function validFile(\Valid_File $validator): bool
    {
        return $validator->validate($_FILES, $validator->getKey());
    }

    /**
     * @deprecated
     */
    public function getServerUrl(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl();
    }

    /**
     * Return request IP address
     *
     * When run behind a reverse proxy, REMOTE_ADDR will be the IP address of the
     * reverse proxy, use this method if you want to get the actual ip address
     * of the request without having to deal with reverse-proxy or not.
     */
    public function getIPAddress(): string
    {
        return \Tuleap\Http\Server\IPAddressExtractor::getIPAddressFromServerParams($_SERVER);
    }

    public function isAjax(): bool
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
    public function get(string $variable): mixed
    {
        return $this->_get($variable, $this->params);
    }

    /**
     * Add a param and/or set its value
     *
     */
    public function set(string $name, mixed $value): void
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
     * @param string $idx The index of the variable array in $this->params.
     * @param string Name of the parameter to get.
     *
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     *
     * @psalm-taint-source input
     */
    public function getInArray(string $idx, string $variable): mixed
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
    public function _get(string $variable, $array): mixed //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     */
    public function exist(string $variable): bool
    {
        return $this->_exist($variable, $this->params);
    }

    /**
     * Check if $variable exists in $array.
     *
     * @access protected
     * @param string $variable Name of the parameter.
     */
    protected function _exist(string $variable, array $array): bool //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return isset($array[$variable]);
    }

    /**
     * Check if $variable exists and is not empty in user submitted parameters.
     *
     * @param string $variable Name of the parameter.
     */
    public function existAndNonEmpty(string $variable): bool
    {
        return ($this->exist($variable) && trim($this->params[$variable]) != '');
    }

    public function valid(\Valid $validator): bool
    {
        return $validator->validate($this->get($validator->getKey()));
    }

    public function validArray(\Valid $validator): bool
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

    public function validInArray(string $index, \Valid $validator): bool
    {
        return $validator->validate($this->getInArray($index, $validator->getKey()));
    }

    public function validKey(string $key, \Rule $rule): bool
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
    public function getValidated(string $variable, $validator = 'string', $default_value = null): mixed
    {
        $is_valid = false;
        if ($v = ValidFactory::getInstance($validator, $variable)) {
            $is_valid = $this->valid($v);
        } else {
            trigger_error('Validator ' . $validator . ' is not found', E_USER_ERROR);
        }
        return $is_valid ? $this->get($variable) : $default_value;
    }

    public function getToggleVariable(string $variable): int
    {
        if ($this->exist($variable) && (int) $this->get($variable) === 1) {
            return 1;
        }
        return 0;
    }

    /**
     * Return the authenticated current user if any (null otherwise)
     */
    public function getCurrentUser(): \PFUser
    {
        if ($this->current_user === null) {
            $this->current_user = UserManager::instance()->getCurrentUser();
        }
        return $this->current_user;
    }

    public function checkUserIsSuperUser(): void
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
    public function setCurrentUser(\PFUser $user): void
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
    public function getJsonDecodedBody(): mixed
    {
        return json_decode((string) file_get_contents('php://input'));
    }
}
