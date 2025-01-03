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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyMisusageException;
use Tuleap\Webdav\Authentication\HeadersSender;

/**
 * Class of authentication
 */
class WebDAVAuthentication
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var HeadersSender
     */
    private $headers_sender;
    /**
     * @var HTTPBasicAuthUserAccessKeyAuthenticator
     */
    private $access_key_authenticator;

    public function __construct(
        UserManager $user_manager,
        private User_LoginManager $login_manager,
        HeadersSender $headers_sender,
        HTTPBasicAuthUserAccessKeyAuthenticator $access_key_authenticator,
    ) {
        $this->user_manager             = $user_manager;
        $this->headers_sender           = $headers_sender;
        $this->access_key_authenticator = $access_key_authenticator;
    }

    /**
     * Authentication method
     *
     * Returns the authenticated user
     */
    public function authenticate(): PFUser
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        $user     = $this->getUser($username, $password);
        if ($user->isAnonymous()) {
            $this->setHeader();
        } else {
            return $user;
        }
    }

    /**
     * Sets the authentication header
     *
     * @psalm-return never-return
     */
    public function setHeader(): void
    {
        $this->headers_sender->sendHeaders();
    }

    /**
     * Returns the content of username field
     */
    private function getUsername(): string
    {
        return $_SERVER['PHP_AUTH_USER'] ?? '';
    }

    /**
     * Returns the content of password field
     *
     */
    private function getPassword(): ConcealedString
    {
        if (! isset($_SERVER['PHP_AUTH_PW'])) {
            return new ConcealedString('');
        }
        return new ConcealedString($_SERVER['PHP_AUTH_PW']);
    }

    /**
     * Returns the authenticated user or anonymous user
     */
    public function getUser(string $username, ConcealedString $password): PFUser
    {
        try {
            $user = $this->access_key_authenticator->getUser($username, $password, \HTTPRequest::instance()->getIPAddress());
        } catch (HTTPBasicAuthUserAccessKeyMisusageException $exception) {
            $this->setHeader();
        }
        return $user ?? $this->getUserFromUsernameAndPassword($username, $password);
    }

    private function getUserFromUsernameAndPassword(string $username, ConcealedString $password): PFUser
    {
        try {
            $user = $this->login_manager->authenticate($username, $password);
            $this->login_manager->validateAndSetCurrentUser($user);
            return $user;
        } catch (\User_LoginException $e) {
            return $this->user_manager->getUserAnonymous();
        }
    }
}
