<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\User;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialNotFoundException;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSession;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSessionNotInitializedException;

class DynamicUserCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $language = \Mockery::mock(\BaseLanguage::class);
        $GLOBALS['Language'] = $language;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testDynamicUserIsRetrievedLoggedInWhenSessionIsInitialized()
    {
        $dynamic_credential_session = \Mockery::mock(DynamicCredentialSession::class);
        $credential                 = \Mockery::mock(Credential::class);
        $credential->shouldReceive('hasExpired')->andReturn(false);
        $dynamic_credential_session->shouldReceive('getAssociatedCredential')->andReturn($credential);
        $user_manager = \Mockery::mock(\UserManager::class);
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage');
        $clean_up_function = function () {
        };

        $dynamic_user_creator = new DynamicUserCreator($dynamic_credential_session, $user_manager, 'Realname', $clean_up_function);

        $user = $dynamic_user_creator->getDynamicUser([]);
        $this->assertTrue($user->isLoggedIn());
    }

    public function testDynamicUserIsRetrievedLoggedOutWhenSessionIsNotInitialized()
    {
        $dynamic_credential_session = \Mockery::mock(DynamicCredentialSession::class);
        $dynamic_credential_session->shouldReceive('getAssociatedCredential')->andThrow(DynamicCredentialSessionNotInitializedException::class);
        $user_manager = \Mockery::mock(\UserManager::class);
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage');
        $clean_up_function = function () {
        };

        $dynamic_user_creator = new DynamicUserCreator($dynamic_credential_session, $user_manager, 'Realname', $clean_up_function);

        $user = $dynamic_user_creator->getDynamicUser([]);
        $this->assertFalse($user->isLoggedIn());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCurrentUserIsLogoutWhenCredentialIsExpired()
    {
        $dynamic_credential_session = \Mockery::mock(DynamicCredentialSession::class);
        $credential                 = \Mockery::mock(Credential::class);
        $credential->shouldReceive('hasExpired')->andReturn(true);
        $dynamic_credential_session->shouldReceive('getAssociatedCredential')->andReturn($credential);
        $user_manager = \Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('logout');
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage');
        $clean_up = \Mockery::mock();
        $clean_up->shouldReceive('clean_up_expired')->once();

        $dynamic_user_creator = new DynamicUserCreator(
            $dynamic_credential_session,
            $user_manager,
            'Realname',
            [$clean_up, 'clean_up_expired']
        );

        $this->expectException(InvalidStateCleanUpDoesNotInterruptException::class);

        $dynamic_user_creator->getDynamicUser([]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCurrentUserIsLogoutWhenCredentialIsNotFound()
    {
        $dynamic_credential_session = \Mockery::mock(DynamicCredentialSession::class);
        $dynamic_credential_session->shouldReceive('getAssociatedCredential')->andThrow(CredentialNotFoundException::class);
        $user_manager = \Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('logout');
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage');
        $clean_up = \Mockery::mock();
        $clean_up->shouldReceive('clean_up_not_found')->once();

        $dynamic_user_creator = new DynamicUserCreator(
            $dynamic_credential_session,
            $user_manager,
            'Realname',
            [$clean_up, 'clean_up_not_found']
        );

        $this->expectException(InvalidStateCleanUpDoesNotInterruptException::class);

        $dynamic_user_creator->getDynamicUser([]);
    }
}
