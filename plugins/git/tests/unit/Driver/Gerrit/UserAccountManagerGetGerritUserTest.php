<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Driver\Gerrit;

use Event;
use EventManager;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServerFactory;
use LDAP_User;
use LDAPResult;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserAccountManagerGetGerritUserTest extends TestCase
{
    private string $ldap_login;
    private LDAPResult&MockObject $ldap_result;

    #[\Override]
    protected function setUp(): void
    {
        $event_manager = new EventManager();
        $event_manager->addListener(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $this, 'hookReturnsLdapUser', false);
        EventManager::setInstance($event_manager);

        $this->ldap_login  = 'bla blo';
        $this->ldap_result = $this->createMock(LDAPResult::class);
        $this->ldap_result->method('getLogin')->willReturn($this->ldap_login);
    }

    #[\Override]
    protected function tearDown(): void
    {
        EventManager::clearInstance();
    }

    public function hookReturnsLdapUser($params): void
    {
        $params['ldap_user'] = new LDAP_User($params['user'], $this->ldap_result);
    }

    public function testItCreatesGerritUserFromLdapUser(): void
    {
        $user_manager = new Git_Driver_Gerrit_UserAccountManager(
            $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class),
            $this->createMock(Git_RemoteServer_GerritServerFactory::class)
        );

        $gerrit_user = $user_manager->getGerritUser(UserTestBuilder::buildWithDefaults());
        self::assertEquals($this->ldap_login, $gerrit_user->getWebUserName());
    }
}
