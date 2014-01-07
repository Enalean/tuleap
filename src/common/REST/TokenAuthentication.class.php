<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\RestException;

use EventManager;
use UserManager;
use User_LoginManager;

class TokenAuthentication implements iAuthenticate {
    /** @var UserManager */
    private $user_manager;

    const HTTP_TOKEN_HEADER     = 'X-Auth-Token';
    const PHP_HTTP_TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    const HTTP_USER_HEADER      = 'X-Auth-UserId';
    const PHP_HTTP_USER_HEADER  = 'HTTP_X_AUTH_USERID';

    public function __construct() {
        $this->user_manager = UserManager::instance();
    }

    public function __isAllowed() {
        try {
            if (! $this->cookieBasedAuthentication()) {
                $this->tokenBasedAuthentication();
            }
            return true;
        } catch (\User_LoginException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (\Rest_Exception_InvalidTokenException $exception) {
            throw new RestException(401, $exception->getMessage());
        }
        throw new RestException(401, 'Authentication required (headers: ' .self::HTTP_TOKEN_HEADER. ', ' .self::HTTP_USER_HEADER. ')');
    }

    /**
     * We need it to browse the API as we are logged in through the Web UI
     */
    private function cookieBasedAuthentication() {
        $current_user = $this->user_manager->getCurrentUser();
        if ($this->isCsrfSafe() && ! $current_user->isAnonymous()) {
            return true;
        }
        return false;
    }

    /**
     * @todo We should really check based on a csrf token but no way to get it done yet
     * @return boolean
     */
    private function isCsrfSafe() {
        if ($this->isRequestFromSelf()) {
            return true;
        }
        return false;
    }

    private function isRequestFromSelf() {
        return $this->getQueryHost() === $this->getRefererHost();
    }

    private function getQueryHost() {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            return $this->getUrlBase($scheme.$_SERVER['HTTP_HOST']);
        }
        return $this->getUrlBase(get_server_url());
    }

    private function getRefererHost() {
        return $this->getUrlBase($_SERVER['HTTP_REFERER']);
    }

    private function getUrlBase($url) {
        $parsed_url = parse_url($url);
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host   = isset($parsed_url['host'])   ? $parsed_url['host'] : '';
        $port   = isset($parsed_url['port'])   ? ':' . $parsed_url['port'] : '';

        return "$scheme$host$port";
    }

    private function tokenBasedAuthentication() {
        $login_manager = new User_LoginManager(
            EventManager::instance(),
            $this->user_manager
        );
        $login_manager->validateAndSetCurrentUser(
            $this->getUserFromToken()
        );
    }

    /**
     * @return PFUser
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromToken() {
        if (! isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER])) {
            throw new RestException(401, self::HTTP_TOKEN_HEADER.' HTTP header required');
        }
        if (! isset($_SERVER[self::PHP_HTTP_USER_HEADER])) {
            throw new RestException(401, self::HTTP_USER_HEADER.' HTTP header required');
        }

        $token = new \Rest_Token(
            $_SERVER[self::PHP_HTTP_USER_HEADER],
            $_SERVER[self::PHP_HTTP_TOKEN_HEADER]
        );

        $token_dao = new \Rest_TokenDao();
        $token_manager = new \Rest_TokenManager(
            $token_dao,
            new \Rest_TokenFactory($token_dao),
            $this->user_manager
        );
        return $token_manager->checkToken($token);
    }
}
