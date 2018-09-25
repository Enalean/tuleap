<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use TuleapTestCase;

class SessionManagerTest extends TuleapTestCase
{
    const SESSION_LIFETIME_2_WEEKS = 1209600;
    const CURRENT_TIME             = 1481202269;

    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \RandomNumberGenerator
     */
    private $random_number_generator;
    /**
     * @var \SessionDao
     */
    private $session_dao;

    public function setUp()
    {
        parent::setUp();
        $this->user_manager            = mock('UserManager');
        $this->random_number_generator = mock('RandomNumberGenerator');
        $this->session_dao             = mock('SessionDao');
    }

    public function itThrowsAnExceptionWhenTheSessionIdentifierIsMalformed()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = 'malformed_session_identifier';

        $this->expectException('Tuleap\User\InvalidSessionException');
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function itThrowsAnExceptionWhenTheSessionIsNotFound()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = '1.random_string';

        stub($this->session_dao)->searchById('1', self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->returns(false);

        $this->expectException('Tuleap\User\InvalidSessionException');
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function itThrowsAnExceptionWhenTokenDoesNotMatch()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id         = '1';
        $session_token      = 'token';
        $session_identifier = "$session_id.$session_token";

        stub($this->session_dao)->searchById($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->returns(
            array('session_hash' => 'expected_token')
        );

        $this->expectException('Tuleap\User\InvalidSessionException');
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function itThrowsAnExceptionWhenTheUserCanNotBeRetrieved()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";

        stub($this->session_dao)->searchById($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->returns(
            array(
                'session_hash' => $hashed_session_token,
                'user_id'      => '101'
            )
        );

        $this->expectException('Tuleap\User\InvalidSessionException');
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function itGetsTheUserFromTheSessionIdentifier()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";
        $user_id              = '101';

        stub($this->session_dao)->searchById($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->returns(
            array(
                'session_hash' => $hashed_session_token,
                'user_id'      => $user_id
            )
        );
        $user = mock('PFUser');
        stub($this->user_manager)->getUserById($user_id)->returns($user);

        $user->expectOnce('setSessionId', array($session_id));
        $user->expectOnce('setSessionHash', array("$session_id.$session_token"));
        $session_user = $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
        $this->assertIdentical($user, $session_user);
    }

    public function itThrowsAnExceptionWhenTheSessionCanNotBeCreated()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        stub($this->session_dao)->create()->returns(null);
        $user    = mock('PFUser');
        $request = mock('HTTPRequest');

        $this->expectException('Tuleap\User\SessionNotCreatedException');
        $session_manager->createSession($user, $request, self::CURRENT_TIME);
    }

    public function itCreatesANewSession()
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id   = '1';
        $user         = mock('PFUser');
        $request      = mock('HTTPRequest');
        $random_token = 'random_token';
        stub($this->session_dao)->create()->returns($session_id);
        stub($this->random_number_generator)->getNumber()->returns($random_token);

        $user->expectOnce('setSessionId', array($session_id));
        $user->expectOnce('setSessionHash', array("$session_id.$random_token"));
        $session_identifier = $session_manager->createSession($user, $request, self::CURRENT_TIME);
        $this->assertEqual("$session_id.$random_token", $session_identifier);
    }
}
