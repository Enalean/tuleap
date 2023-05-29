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

declare(strict_types=1);

namespace Tuleap\DynamicCredentials\User;

use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialNotFoundException;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSession;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSessionNotInitializedException;
use Tuleap\GlobalLanguageMock;

final class DynamicUserCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testDynamicUserIsRetrievedActiveWhenSessionIsInitialized(): void
    {
        $dynamic_credential_session = $this->createMock(DynamicCredentialSession::class);
        $credential                 = $this->createMock(Credential::class);
        $credential->method('hasExpired')->willReturn(false);
        $dynamic_credential_session->method('getAssociatedCredential')->willReturn($credential);
        $user_manager = $this->createMock(\UserManager::class);
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');
        $clean_up_function = function (): void {
        };

        $dynamic_user_creator = new DynamicUserCreator($dynamic_credential_session, $user_manager, 'Realname', $clean_up_function);

        $user = $dynamic_user_creator->getDynamicUser([]);
        self::assertTrue($user->isActive());
    }

    public function testDynamicUserIsRetrievedNotActiveWhenSessionIsNotInitialized(): void
    {
        $dynamic_credential_session = $this->createMock(DynamicCredentialSession::class);
        $dynamic_credential_session->method('getAssociatedCredential')->willThrowException(
            new DynamicCredentialSessionNotInitializedException()
        );
        $user_manager = $this->createMock(\UserManager::class);
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');
        $clean_up_function = function (): void {
        };

        $dynamic_user_creator = new DynamicUserCreator($dynamic_credential_session, $user_manager, 'Realname', $clean_up_function);

        $user = $dynamic_user_creator->getDynamicUser([]);
        self::assertFalse($user->isActive());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCurrentUserIsLogoutWhenCredentialIsExpired(): void
    {
        $dynamic_credential_session = $this->createMock(DynamicCredentialSession::class);
        $credential                 = $this->createMock(Credential::class);
        $credential->method('hasExpired')->willReturn(true);
        $dynamic_credential_session->method('getAssociatedCredential')->willReturn($credential);
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('logout');
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');
        $clean_up_function = function (): void {
            self::assertTrue(true);
        };

        $dynamic_user_creator = new DynamicUserCreator(
            $dynamic_credential_session,
            $user_manager,
            'Realname',
            $clean_up_function,
        );

        $this->expectException(InvalidStateCleanUpDoesNotInterruptException::class);

        $dynamic_user_creator->getDynamicUser([]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCurrentUserIsLogoutWhenCredentialIsNotFound(): void
    {
        $dynamic_credential_session = $this->createMock(DynamicCredentialSession::class);
        $dynamic_credential_session->method('getAssociatedCredential')->willThrowException(new CredentialNotFoundException());
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('logout');
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');
        $clean_up_function = function (): void {
            self::assertTrue(true);
        };

        $dynamic_user_creator = new DynamicUserCreator(
            $dynamic_credential_session,
            $user_manager,
            'Realname',
            $clean_up_function,
        );

        $this->expectException(InvalidStateCleanUpDoesNotInterruptException::class);

        $dynamic_user_creator->getDynamicUser([]);
    }
}
