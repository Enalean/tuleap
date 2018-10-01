<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use Tuleap\User\AccessKey\AccessKey;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyVerificationStringHasher;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\PasswordVerifier;
use User_LoginManager;
use User_PasswordExpirationChecker;
use EventManager;
use Tuleap\REST\Exceptions\NoAuthenticationHeadersException;
use Rest_TokenDao;
use Rest_TokenManager;
use Rest_TokenFactory;
use Rest_Token;
use PasswordHandlerFactory;

class UserManager {

    /** @var \UserManager */
    private $user_manager;

    /** @var User_LoginManager */
    private $login_manager;

    /**
     * @var AccessKeyVerifier
     */
    private $access_key_verifier;

    const HTTP_TOKEN_HEADER     = 'X-Auth-Token';
    const PHP_HTTP_TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    const HTTP_USER_HEADER      = 'X-Auth-UserId';
    const PHP_HTTP_USER_HEADER  = 'HTTP_X_AUTH_USERID';

    const HTTP_ACCESS_KEY_HEADER     = 'X-Auth-AccessKey';
    const PHP_HTTP_ACCESS_KEY_HEADER = 'HTTP_X_AUTH_ACCESSKEY';

    public function __construct(
        \UserManager $user_manager,
        User_LoginManager $login_manager,
        AccessKeyVerifier $access_key_verifier
    ) {
        $this->user_manager        = $user_manager;
        $this->login_manager       = $login_manager;
        $this->access_key_verifier = $access_key_verifier;
    }

    public static function build()
    {
        $user_manager     = \UserManager::instance();
        $password_handler = PasswordHandlerFactory::getPasswordHandler();
        return new self(
            $user_manager,
            new User_LoginManager(
                EventManager::instance(),
                $user_manager,
                new PasswordVerifier($password_handler),
                new User_PasswordExpirationChecker(),
                $password_handler
            ),
            new AccessKeyVerifier(new AccessKeyDAO(), new AccessKeyVerificationStringHasher(), $user_manager)
        );
    }

    /**
     * Return user of current request in REST context
     *
     * Tries to get authentication scheme from cookie if any, fallback on token
     * or access key authentication
     *
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusSuspendedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_PasswordExpiredException
     *
     * @return \PFUser
     */
    public function getCurrentUser()
    {
        $user = $this->getUserFromCookie();
        if (! $user->isAnonymous()) {
            return $user;
        }
        try {
            $user = $this->getUserFromTuleapRESTAuthenticationFlows();
        } catch (NoAuthenticationHeadersException $exception) {
            return $this->user_manager->getUserAnonymous();
        }
        if ($user === null) {
            return $this->user_manager->getUserAnonymous();
        }
        $this->login_manager->validateAndSetCurrentUser($user);
        return $user;
    }

    /**
     * We need it to browse the API as we are logged in through the Web UI
     * @throws \User_PasswordExpiredException
     */
    private function getUserFromCookie() {
        $current_user = $this->user_manager->getCurrentUser();
        if (! $current_user->isAnonymous()) {
            $password_expiration_checker = new User_PasswordExpirationChecker();
            $password_expiration_checker->checkPasswordLifetime($current_user);
        }
        return $current_user;
    }

    /**
     * @return null|\PFUser
     * @throws NoAuthenticationHeadersException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromTuleapRESTAuthenticationFlows()
    {
        if ($this->isTryingToUseAccessKeyAuthentication()) {
            return $this->getUserFromAccessKey();
        }
        if ($this->isTryingToUseTokenAuthentication()) {
            return $this->getUserFromToken();
        }
        return null;
    }

    /**
     * @return bool
     */
    private function isTryingToUseAccessKeyAuthentication()
    {
        return isset($_SERVER[self::PHP_HTTP_ACCESS_KEY_HEADER]);
    }

    /**
     * @return bool
     */
    private function isTryingToUseTokenAuthentication()
    {
        return isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER]);
    }

    private function getUserFromAccessKey()
    {
        if (! isset($_SERVER[self::PHP_HTTP_ACCESS_KEY_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_ACCESS_KEY_HEADER);
        }

        $access_key_identifier = $_SERVER[self::PHP_HTTP_ACCESS_KEY_HEADER];
        $access_key            = AccessKey::buildFromIdentifier($access_key_identifier);

        $request = \HTTPRequest::instance();
        return $this->access_key_verifier->getUser($access_key, $request->getIPAddress());
    }

    /**
     * @return \PFUser
     * @throws NoAuthenticationHeadersException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromToken() {
        if (! isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        if (! isset($_SERVER[self::PHP_HTTP_USER_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        $token = new Rest_Token(
            $_SERVER[self::PHP_HTTP_USER_HEADER],
            $_SERVER[self::PHP_HTTP_TOKEN_HEADER]
        );

        $token_dao = new Rest_TokenDao();
        $token_manager = new Rest_TokenManager(
            $token_dao,
            new Rest_TokenFactory($token_dao),
            $this->user_manager
        );
        return $token_manager->checkToken($token);
    }
}
