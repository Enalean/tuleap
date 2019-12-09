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

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAP_User.class.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAPResult.class.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserAccountManagerGetGerritUserTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $event_manager = new EventManager();
        $event_manager->addListener(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $this, 'hookReturnsLdapUser', false);
        EventManager::setInstance($event_manager);

        $this->ldap_login  = 'bla blo';
        $this->ldap_result = stub('LDAPResult')->getLogin()->returns($this->ldap_login);
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function hookReturnsLdapUser($params)
    {
        $params['ldap_user'] = new LDAP_User($params['user'], $this->ldap_result);
    }

    public function itCreatesGerritUserFromLdapUser()
    {
        $user_manager = new Git_Driver_Gerrit_UserAccountManager(
            mock('Git_Driver_Gerrit_GerritDriverFactory'),
            mock('Git_RemoteServer_GerritServerFactory')
        );

        $gerrit_user = $user_manager->getGerritUser(mock('PFUser'));
        $this->assertEqual($gerrit_user->getWebUserName(), $this->ldap_login);
    }
}
