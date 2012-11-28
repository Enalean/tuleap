<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * Class of authentication
 */
class WebDAVAuthentication {

    /**
     * Authentication method
     *
     * Returns the authenticated user
     *
     * @return PFUser
     */
    function authenticate() {

        // test if username field is empty
        if (!$this->issetUsername()) {
            $this->setHeader();
        } else {
            $username = $this->getUsername();
            $password = $this->getPassword();
            $user = $this->getUser($username, $password);
            // Ask again for authentication if the user entered a wrong username or password
            // if fields are left blank the user is considered as anonymous unless Codendi don't accept anonymous access
            if ($user->isAnonymous() && ($username || $password || !$GLOBALS['sys_allow_anon'])) {
                $this->setHeader();
            } else {
                return $user;
            }
        }

    }

    /**
     * Returns whether the username field is empty or not
     *
     * @return Boolean
     */
    function issetUsername() {

        return isset($_SERVER['PHP_AUTH_USER']);

    }

    /**
     * Sets the authentication header
     *
     * @return void
     */
    function setHeader() {

        header('WWW-Authenticate: Basic realm="'.$GLOBALS['sys_name'].' WebDAV Authentication"');
        header('HTTP/1.0 401 Unauthorized');

        // text returned when user hit cancel
        echo $GLOBALS['Language']->getText('plugin_webdav_common', 'authentication_required');

        // The HTTP_BasicAuth (and digest) will return a 401 statuscode.
        // If there is no die() after that, the server will just do it's thing as usual
        // and override it with it's own statuscode (200, 404, 207, 201, or whatever was appropriate).
        // So the die() actually makes sure that the php script doesn't continue if the client
        // has an incorrect or no username and password.
        die();

    }

    /**
     * Returns the content of username field
     *
     * @return String
     */
    function getUsername() {

        return $_SERVER['PHP_AUTH_USER'];

    }

    /**
     * Returns the content of password field
     *
     * @return String
     */
    function getPassword() {

        return $_SERVER['PHP_AUTH_PW'];

    }

    /**
     * Returns the authenticated user or anonymous user
     *
     * @param String $username
     *
     * @param String $password
     *
     * @return PFUser
     */
    function getUser($username, $password) {

        return UserManager::instance()->login($username, $password);

    }

}

?>