<?php
/**
 * Copyright (c) Enalean SAS, 2015 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Tuleap\Webdav\Authentication\HeadersSender;

/**
 * Class of authentication
 */
class WebDAVAuthentication
{
    /**
     * @var HeadersSender
     */
    private $headers_sender;

    public function __construct(HeadersSender $headers_sender)
    {
        $this->headers_sender = $headers_sender;
    }

    /**
     * Authentication method
     *
     * Returns the authenticated user
     *
     * @return PFUser
     */
    public function authenticate()
    {
        // test if username field is empty
        if (!$this->issetUsername()) {
            $this->setHeader();
        } else {
            $username = $this->getUsername();
            $password = $this->getPassword();
            $user = $this->getUser($username, $password);
            // Ask again for authentication if the user entered a wrong username or password
            // if fields are left blank the user is considered as anonymous unless Tuleap don't accept anonymous access
            if ($user->isAnonymous() && ($username || $password || ! ForgeConfig::areAnonymousAllowed())) {
                $this->setHeader();
            } else {
                return $user;
            }
        }
    }

    /**
     * Returns whether the username field is empty or not
     *
     * @return bool
     */
    public function issetUsername()
    {
        return isset($_SERVER['PHP_AUTH_USER']);
    }

    /**
     * Sets the authentication header
     *
     */
    public function setHeader(): void
    {
        $this->headers_sender->sendHeaders();
    }

    /**
     * Returns the content of username field
     *
     * @return String
     */
    public function getUsername()
    {
        return $_SERVER['PHP_AUTH_USER'];
    }

    /**
     * Returns the content of password field
     *
     * @return String
     */
    public function getPassword()
    {
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
    public function getUser($username, $password)
    {
        return UserManager::instance()->login($username, $password);
    }
}
