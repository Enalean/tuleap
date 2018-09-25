<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\User;

use HTTPRequest;
use PFUser;
use RandomNumberGenerator;
use SessionDao;
use UserManager;

class SessionManager
{
    const HASH_ALGORITHM               = 'sha256';
    const SESSION_IDENTIFIER_SEPARATOR = '.';

    /**
     * @var SessionDao
     */
    private $session_dao;
    /**
     * @var RandomNumberGenerator
     */
    private $random_number_generator;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        SessionDao $session_dao,
        RandomNumberGenerator $random_number_generator
    ) {
        $this->session_dao             = $session_dao;
        $this->random_number_generator = $random_number_generator;
        $this->user_manager            = $user_manager;
    }

    /**
     * @return PFUser
     * @throws InvalidSessionException
     */
    public function getUser($session_identifier, $current_time, $session_lifetime)
    {
        list($session_id, $session_token) = $this->getSessionIdentifierParts($session_identifier);

        $session = $this->session_dao->searchById($session_id, $current_time, $session_lifetime);
        if ($session === false) {
            throw new InvalidSessionException();
        }

        $hashed_session_token = hash(self::HASH_ALGORITHM, $session_token);
        if (! hash_equals($session['session_hash'], $hashed_session_token)) {
            throw new InvalidSessionException();
        }

        $user = $this->user_manager->getUserById($session['user_id']);

        if ($user === null) {
            throw new InvalidSessionException();
        }

        $user->setSessionId($session_id);
        $user->setSessionHash($session_identifier);
        return $user;
    }

    /**
     * @return string
     * @throws SessionNotCreatedException
     */
    public function createSession(PFUser $user, HTTPRequest $request, $current_time)
    {
        $token        = $this->random_number_generator->getNumber();
        $hashed_token = hash(self::HASH_ALGORITHM, $token);
        $session_id   = $this->session_dao->create(
            $user->getId(),
            $hashed_token,
            $request->getIPAddress(),
            $current_time
        );

        if ($session_id === null) {
            throw new SessionNotCreatedException();
        }

        $session_identifier = $session_id . self::SESSION_IDENTIFIER_SEPARATOR . $token;
        $user->setSessionId($session_id);
        $user->setSessionHash($session_identifier);

        return $session_identifier;
    }

    /**
     * @throws SessionDataAccessException
     */
    public function destroyCurrentSession(PFUser $user)
    {
        $session_id = $user->getSessionId();
        $is_deleted = $this->session_dao->deleteSessionById($session_id);
        if ($is_deleted === false) {
            throw new SessionDataAccessException();
        }
        $user->setSessionId(false);
        $user->setSessionHash(false);
    }

    /**
     * @throws SessionDataAccessException
     */
    public function destroyAllSessions(PFUser $user)
    {
        $is_deleted = $this->session_dao->deleteSessionByUserId($user->getId());
        if ($is_deleted === false) {
            throw new SessionDataAccessException();
        }
        $user->setSessionId(false);
        $user->setSessionHash(false);
    }

    /**
     * @throws SessionDataAccessException
     */
    public function destroyAllSessionsButTheCurrentOne(PFUser $user)
    {
        $is_deleted = $this->session_dao->deleteAllSessionsByUserIdButTheCurrentOne($user->getId(), $user->getSessionId());
        if ($is_deleted === false) {
            throw new SessionDataAccessException();
        }
    }

    /**
     * @return array
     * @throws InvalidSessionException
     */
    private function getSessionIdentifierParts($session_identifier)
    {
        $session_identifier_parts = explode(self::SESSION_IDENTIFIER_SEPARATOR, $session_identifier);
        if (count($session_identifier_parts) !== 2) {
            throw new InvalidSessionException();
        }
        return $session_identifier_parts;
    }
}
