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

final class SessionManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private const SESSION_LIFETIME_2_WEEKS = 1209600;
    private const CURRENT_TIME             = 1481202269;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->user_manager            = \Mockery::spy(\UserManager::class);
        $this->random_number_generator = \Mockery::spy(\RandomNumberGenerator::class);
        $this->session_dao             = \Mockery::spy(\SessionDao::class);
    }

    public function testItThrowsAnExceptionWhenTheSessionIdentifierIsMalformed(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = 'malformed_session_identifier';

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function testItThrowsAnExceptionWhenTheSessionIsNotFound(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_identifier = '1.random_string';

        $this->session_dao->shouldReceive('searchById')->with('1', self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->andReturns(false);

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function testItThrowsAnExceptionWhenTokenDoesNotMatch(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id         = '1';
        $session_token      = 'token';
        $session_identifier = "$session_id.$session_token";

        $this->session_dao->shouldReceive('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->andReturns(array('session_hash' => 'expected_token'));

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function testItThrowsAnExceptionWhenTheUserCanNotBeRetrieved(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";

        $this->session_dao->shouldReceive('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->andReturns(array(
            'session_hash' => $hashed_session_token,
            'user_id'      => '101'
        ));

        $this->expectException(\Tuleap\User\InvalidSessionException::class);
        $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
    }

    public function testItGetsTheUserFromTheSessionIdentifier(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id           = '1';
        $session_token        = 'token';
        $hashed_session_token = hash(SessionManager::HASH_ALGORITHM, $session_token);
        $session_identifier   = "$session_id.$session_token";
        $user_id              = '101';

        $this->session_dao->shouldReceive('searchById')->with($session_id, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS)->andReturns(array(
            'session_hash' => $hashed_session_token,
            'user_id'      => $user_id
        ));
        $user = \Mockery::spy(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->with($user_id)->andReturns($user);

        $user->shouldReceive('setSessionId')->with($session_id)->once();
        $user->shouldReceive('setSessionHash')->with("$session_id.$session_token")->once();
        $session_user = $session_manager->getUser($session_identifier, self::CURRENT_TIME, self::SESSION_LIFETIME_2_WEEKS);
        $this->assertSame($user, $session_user);
    }

    public function testItThrowsAnExceptionWhenTheSessionCanNotBeCreated(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $this->session_dao->shouldReceive('create')->andReturns(null);
        $user    = \Mockery::spy(\PFUser::class);
        $request = \Mockery::spy(\HTTPRequest::class);

        $this->expectException(\Tuleap\User\SessionNotCreatedException::class);
        $session_manager->createSession($user, $request, self::CURRENT_TIME);
    }

    public function testItCreatesANewSession(): void
    {
        $session_manager = new SessionManager($this->user_manager, $this->session_dao, $this->random_number_generator);

        $session_id   = '1';
        $user         = \Mockery::spy(\PFUser::class);
        $request      = \Mockery::spy(\HTTPRequest::class);
        $random_token = 'random_token';
        $this->session_dao->shouldReceive('create')->andReturns($session_id);
        $this->random_number_generator->shouldReceive('getNumber')->andReturns($random_token);

        $user->shouldReceive('setSessionId')->with($session_id)->once();
        $user->shouldReceive('setSessionHash')->with("$session_id.$random_token")->once();
        $session_identifier = $session_manager->createSession($user, $request, self::CURRENT_TIME);
        $this->assertEquals("$session_id.$random_token", $session_identifier);
    }
}
