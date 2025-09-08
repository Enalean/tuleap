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

class HTTPRequest extends Codendi_Request
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct($_REQUEST);
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
     *
     * @return bool
     */
    public function isPost()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return HTTPRequest
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    /**
     * Validate file upload.
     *
     * @param  Valid_File Validator for files.
     * @return bool
     */
    public function validFile(&$validator)
    {
        if ($validator instanceof \Valid_File) {
            return $validator->validate($_FILES, $validator->getKey());
        } else {
            return false;
        }
    }

    /**
     * Get the value of $variable in $array. If magic_quotes are enabled, the
     * value is escaped.
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @param array $array Name of the parameter to get.
     */
    #[\Override]
    public function _get($variable, $array)
    {
        if ($this->_exist($variable, $array)) {
            return $array[$variable];
        } else {
            return false;
        }
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
}
