<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;

final class SessionManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SESSION_LIFETIME_2_WEEKS = 1209600;
    private const CURRENT_TIME             = 1481202269;

    /**
     * @var \UserManager&MockObject
     */
    private $user_manager;
    /**
     * @var \RandomNumberGenerator&MockObject
     */
    private $random_number_generator;
    /**
     * @var \SessionDao&MockObject
     */
    private $session_dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user_manager            = $this->createMock(\UserManager::class);
        $this->random_number_generator = $this->createMock(\RandomNumberGenerator::class);
        $this->session_dao             = $this->createMock(\SessionDao::class);
    }

    public function testItThrowsAnExceptionWhenTheSessionIdentifierIsMalformed(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = 'malformed_session_identifier';

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS, 'User agent');
    }

    public function testItThrowsAnExceptionWhenTheSessionIsNotFound(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = '1.random_string';

        $this->session_dao->method('searchById')->with('1', self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->willReturn(null);

        $this->expectException(\Tuleap\User\InvalidSessionException::class);

        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS, 'User agent');
    }

    public function testItThrowsAnExceptionWhenTokenDoesNotMatch(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id         = '1';
        $session_token      = 'token';
        $session_identifier = "$session_id.$session_token";

        $this->session_dao->method('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->willReturn(['session_hash' => 'expected_token']);

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS, 'User agent');
    }

    public function testItThrowsAnExceptionWhenTheUserCanNotBeRetrieved(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";

        $this->session_dao->method('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->willReturn([
            'session_hash' => $hashed_session_token,
            'user_id'      => '101',
        ]);

        $this->user_manager->method('getUserById')->willReturn(null);

        $this->expectException(\Tuleap\User\InvalidSessionException::class);

        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS, 'User agent');
    }

    public function testItGetsTheUserFromTheSessionIdentifier(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";
        $user_id              = '101';

        $this->session_dao->method('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->willReturn([
            'session_hash' => $hashed_session_token,
            'user_id'      => $user_id,
            'user_agent'   => 'old_user_agent',
        ]);

        $this->session_dao->expects(self::once())->method('updateUserAgentByID');
        $user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserById')->with($user_id)->willReturn($user);

        $user->expects(self::once())->method('setSessionId')->with($session_id);
        $user->expects(self::once())->method('setSessionHash')->with("$session_id.$session_token");
        $session_user = $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS, 'User agent');
        self::assertSame($user, $session_user);
    }

    public function testItThrowsAnExceptionWhenTheSessionCanNotBeCreated(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $this->session_dao->method('create')->willThrowException(
            new \RuntimeException('Something really bad happened, could not create the session in the DB')
        );
        $user    = UserTestBuilder::aUser()->build();
        $request = $this->createMock(\HTTPRequest::class);

        $this->random_number_generator->method('getNumber')->willReturn("1");

        $this->expectException(\Tuleap\User\SessionNotCreatedException::class);

        $session_manager->createSession($user, $request, self::CURRENT_TIME);
    }

    public function testItCreatesANewSession(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id   = 1;
        $user         = $this->createMock(\PFUser::class);
        $request      = $this->createMock(\HTTPRequest::class);
        $random_token = 'random_token';
        $this->session_dao->method('create')->willReturn($session_id);
        $this->random_number_generator->method('getNumber')->willReturn($random_token);

        $user->method('getId')->willReturn(101);
        $user->expects(self::once())->method('setSessionId')->with($session_id);
        $user->expects(self::once())->method('setSessionHash')->with("$session_id.$random_token");

        $request->method('getIPAddress')->willReturn('ip');
        $request->method('getFromServer')->willReturn(null);

        $session_identifier = $session_manager->createSession($user, $request, self::CURRENT_TIME);
        self::assertEquals("$session_id.$random_token", $session_identifier);
    }
}
