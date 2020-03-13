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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../../ldap/include/LDAP_User.class.php';
require_once __DIR__ . '/../../../../../ldap/include/LDAPResult.class.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserAccountManagerGetGerritUserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $event_manager = new EventManager();
        $event_manager->addListener(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $this, 'hookReturnsLdapUser', false);
        EventManager::setInstance($event_manager);

        $this->ldap_login  = 'bla blo';
        $this->ldap_result = \Mockery::spy(\LDAPResult::class)->shouldReceive('getLogin')->andReturns($this->ldap_login)->getMock();
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function hookReturnsLdapUser($params)
    {
        $params['ldap_user'] = new LDAP_User($params['user'], $this->ldap_result);
    }

    public function testItCreatesGerritUserFromLdapUser()
    {
        $user_manager = new Git_Driver_Gerrit_UserAccountManager(
            \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class),
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class)
        );

        $gerrit_user = $user_manager->getGerritUser(\Mockery::spy(\PFUser::class));
        $this->assertEquals($this->ldap_login, $gerrit_user->getWebUserName());
    }
}
