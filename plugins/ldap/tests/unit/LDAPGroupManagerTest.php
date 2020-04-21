<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright © STMicroelectronics, 2009. All Rights Reserved.
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

namespace Tuleap\LDAP;

use LDAP_GroupManager;
use LDAP_ProjectGroupDao;
use LDAP_UserManager;
use LDAPResult;
use LDAPResultIterator;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class LDAPGroupManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private function getLdapResult($method, $result): LDAPResultIterator
    {
        $ldapRes = \Mockery::spy(\LDAPResult::class);
        $ldapRes->shouldReceive($method)->andReturns($result);
        $ldapResIter = \Mockery::spy(\LDAPResultIterator::class);
        $ldapResIter->shouldReceive('count')->andReturns(1);
        $ldapResIter->shouldReceive('current')->andReturns($ldapRes);
        return $ldapResIter;
    }

    public function testDoesBuildANotificationOnUpdate(): void
    {
        $ldapResIter = $this->getLdapResult('getGroupMembers', array(
            'eduid=edA,ou=people,dc=codendi,dc=com',
            'eduid=edE,ou=people,dc=codendi,dc=com'
        ));

        $ldap = \Mockery::mock(
            \LDAP::class,
            [
                ['server' => 'server'],
                Mockery::mock(\Psr\Log\LoggerInterface::class)
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $ldap->shouldReceive('searchGroupMembers')->andReturns($ldapResIter);
        $ldap->shouldReceive('getLdapParam')->with('grp_dn')->andReturns('ou=groups,dc=codendi,dc=com');

        $ldap_user_manager = \Mockery::spy(LDAP_UserManager::class);
        $ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturn(array(101,102));
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn("J. Doe");
        $user->shouldReceive('getUserName')->andReturn("jdoe");
        $ldap_user_manager->shouldReceive('getUserFromLdap')->andReturn();

        $notm = \Mockery::spy(\Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager::class);
        $notm->shouldReceive('sendNotifications')->once();

        $prjm = \Mockery::spy(\ProjectManager::class);
        $prjm->shouldReceive('getProject')->andReturn(\Mockery::spy(\Project::class));

        $grpManager        = Mockery::mock(
            LDAP_GroupManager::class,
            [
                $ldap,
                $ldap_user_manager,
                $prjm,
                $notm
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $grpManager->setGroupDn('cn=whatever,ou=groups,dc=codendi,dc=com');
        $grpManager->setId(42);

        $grpManager->shouldReceive('addUserToGroup');
        $grpManager->shouldReceive('getDbGroupMembersIds')->andReturn([]);
        $grpManager->shouldReceive('getLdapGroupMembersIds')->andReturn([101, 102]);
        $dao = Mockery::mock(LDAP_ProjectGroupDao::class)->shouldReceive('searchByGroupId')->andReturn([])->getMock();
        $dao->shouldReceive('unlinkGroupLdap');
        $dao->shouldReceive('linkGroupLdap');
        $grpManager->shouldReceive('getDao')->andReturn($dao);

        $toAdd = $grpManager->getUsersToBeAdded(LDAP_GroupManager::BIND_OPTION);
        $this->assertCount(2, $toAdd);
        $grpManager->bindWithLdap();
    }

    public function testLdapGroupContainsOtherLdapGroups()
    {
        // Search for umbrella group
        $ldapResIterABCDEF = $this->getLdapResult('getGroupMembers', array('cn=ABC,ou=groups,dc=codendi,dc=com', 'cn=DEF,ou=groups,dc=codendi,dc=com'));
        // Search for first sub-group
        $ldapResIterABC = $this->getLdapResult('getGroupMembers', array('eduid=edA,ou=people,dc=codendi,dc=com'));
        // Search for second sub-group
        $ldapResIterDEF = $this->getLdapResult('getGroupMembers', array('eduid=edE,ou=people,dc=codendi,dc=com'));
        // Search for first user
        $ldapResIterUserA = $this->getLdapResult('getEdUid', 'edA');
        // Search for second user
        $ldapResIterUserE = $this->getLdapResult('getEdUid', 'edE');

        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('searchGroupMembers')->once()->andReturns($ldapResIterABCDEF);
        $ldap->shouldReceive('searchGroupMembers')->once()->andReturns($ldapResIterABC);
        $ldap->shouldReceive('searchGroupMembers')->once()->andReturns($ldapResIterDEF);

        $ldap->shouldReceive('getLdapParam')->with('grp_dn')->andReturns('ou=groups,dc=codendi,dc=com');
        $ldap->shouldReceive('getLdapParam')->with('eduid')->andReturns('eduid');
        $ldap->shouldReceive('getLdapParam')->with('cn')->andReturns('cn');
        $ldap->shouldReceive('getLdapParam')->with('uid')->andReturns('uid');
        $ldap->shouldReceive('getLdapParam')->with('mail')->andReturns('mail');

        $ldap->shouldReceive('searchDn')->once()->andReturns($ldapResIterUserA);
        $ldap->shouldReceive('searchDn')->once()->andReturns($ldapResIterUserE);

        $prjm = \Mockery::spy(\ProjectManager::class);
        $prjm->shouldReceive('getProject')->andReturn(\Mockery::spy(\Project::class));

        $ldap_user_manager = \Mockery::spy(\LDAP_UserManager::class);
        $grpManager        = Mockery::mock(
            LDAP_GroupManager::class,
            [
                $ldap,
                $ldap_user_manager,
                $prjm,
                \Mockery::spy(\Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager::class)
            ]
        )->makePartial();

        $members = $grpManager->getLdapGroupMembers('cn=ABCDEF,ou=groups,dc=codendi,dc=com');

        $this->assertCount(2, $members);
        $this->assertInstanceOf(LDAPResult::class, $members['edA']);
        $this->assertSame('edA', $members['edA']->getEdUid());
        $this->assertInstanceOf(LDAPResult::class, $members['edE']);
        $this->assertSame('edE', $members['edE']->getEdUid());
    }
}
