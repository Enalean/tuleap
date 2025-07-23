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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LDAPGroupManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private function getLdapResult(string $method, string|array $result): MockObject&LDAPResultIterator
    {
        $ldapRes = $this->createMock(\LDAPResult::class);
        $ldapRes->method($method)->willReturn($result);
        $ldapResIter = $this->createMock(\LDAPResultIterator::class);
        $ldapResIter->method('count')->willReturn(1);
        $ldapResIter->method('current')->willReturn($ldapRes);

        return $ldapResIter;
    }

    public function testDoesBuildANotificationOnUpdate(): void
    {
        $ldapResIter = $this->getLdapResult('getGroupMembers', [
            'eduid=edA,ou=people,dc=example,dc=com',
            'eduid=edE,ou=people,dc=example,dc=com',
        ]);

        $ldap = $this->getMockBuilder(\LDAP::class)
            ->onlyMethods([
                'searchGroupMembers',
                'getLdapParam',
            ])
            ->setConstructorArgs([
                ['server' => 'server'],
                new NullLogger(),
            ])->getMock();

        $ldap->method('searchGroupMembers')->willReturn($ldapResIter);
        $ldap->method('getLdapParam')->with('grp_dn')->willReturn('ou=groups,dc=example,dc=com');

        $ldap_user_manager = $this->createMock(LDAP_UserManager::class);
        $ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([101, 102]);

        $user = UserTestBuilder::aUser()->withRealName('J. Doe')->withUserName('jdoe')->build();
        $ldap_user_manager->method('getUserFromLdap')->willReturn($user);

        $notm = $this->createMock(\Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager::class);
        $notm->expects($this->once())->method('sendNotifications');

        $prjm = $this->createMock(\ProjectManager::class);
        $prjm->method('getProject')->willReturn($this->createMock(\Project::class));

        $grpManager = $this->buildLDAPGroupManager($ldap, $ldap_user_manager, $prjm, $notm);

        $grpManager->setGroupDn('cn=whatever,ou=groups,dc=example,dc=com');
        $grpManager->setId(42);



        $toAdd = $grpManager->getUsersToBeAdded(LDAP_GroupManager::BIND_OPTION);
        self::assertCount(2, $toAdd);
        $grpManager->bindWithLdap();
    }

    public function testLdapGroupContainsOtherLdapGroupsWillSearchEachUserOnce(): void
    {
        // Search for umbrella group
        $ldapResIterABCDEF = $this->getLdapResult('getGroupMembers', ['cn=ABC,ou=groups,dc=example,dc=com', 'cn=DEF,ou=groups,dc=example,dc=com']);
        // Search for first sub-group
        $ldapResIterABC = $this->getLdapResult('getGroupMembers', [
            'eduid=edA,ou=people,dc=example,dc=com',
            'eduid=edE,ou=people,dc=example,dc=com',
        ]);
        // Search for second sub-group
        $ldapResIterDEF = $this->getLdapResult('getGroupMembers', ['eduid=edE,ou=people,dc=example,dc=com']);
        // Search for first user
        $ldapResIterUserA = $this->getLdapResult('getEdUid', 'edA');
        // Search for second user
        $ldapResIterUserE = $this->getLdapResult('getEdUid', 'edE');

        $ldap = $this->getMockBuilder(\LDAP::class)
            ->onlyMethods([
                'searchGroupMembers',
                'getLdapParam',
                'searchDn',
            ])
            ->setConstructorArgs([
                ['server' => 'server'],
                new NullLogger(),
            ])->getMock();

        $ldap->method('searchGroupMembers')->willReturnOnConsecutiveCalls(
            $ldapResIterABCDEF,
            $ldapResIterABC,
            $ldapResIterDEF,
        );

        $ldap->method('getLdapParam')->willReturnMap([
            ['grp_dn', 'ou=groups,dc=example,dc=com'],
            ['eduid', 'eduid'],
            ['cn', 'cn'],
            ['uid', 'uid'],
            ['mail', 'mail'],
        ]);

        $ldap->method('searchDn')->willReturnOnConsecutiveCalls($ldapResIterUserA, $ldapResIterUserE);

        $prjm = $this->createMock(\ProjectManager::class);
        $prjm->method('getProject')->willReturn($this->createMock(\Project::class));

        $ldap_user_manager = $this->createMock(\LDAP_UserManager::class);

        $grpManager = $this->buildLDAPGroupManager($ldap, $ldap_user_manager, $prjm, $this->createStub(GroupSyncAdminEmailNotificationsManager::class));

        $members = $grpManager->getLdapGroupMembers('cn=ABCDEF,ou=groups,dc=example,dc=com');

        self::assertCount(2, $members);
        self::assertInstanceOf(LDAPResult::class, $members['edA']);
        self::assertSame('edA', $members['edA']->getEdUid());
        self::assertInstanceOf(LDAPResult::class, $members['edE']);
        self::assertSame('edE', $members['edE']->getEdUid());
    }

    private function buildLDAPGroupManager(
        \LDAP $ldap,
        LDAP_UserManager $ldap_user_manager,
        \ProjectManager $project_manager,
        GroupSyncNotificationsManager $notifications_manager,
    ): LDAP_GroupManager {
        $dao = $this->createStub(LDAP_ProjectGroupDao::class);
        $dao->method('searchByGroupId')->willReturn([]);
        $dao->method('linkGroupLdap');
        $dao->method('unlinkGroupLdap');

        return new class ($ldap, $ldap_user_manager, $project_manager, $notifications_manager, $dao) extends LDAP_GroupManager {
            public function __construct(
                \LDAP $ldap,
                LDAP_UserManager $ldap_user_manager,
                \ProjectManager $project_manager,
                GroupSyncNotificationsManager $notifications_manager,
                private readonly LDAP_ProjectGroupDao $dao,
            ) {
                parent::__construct($ldap, $ldap_user_manager, $project_manager, $notifications_manager, new NullLogger());
            }

            #[\Override]
            protected function addUserToGroup($id, $userId): void
            {
            }

            #[\Override]
            protected function removeUserFromGroup($id, $userId): void
            {
            }

            #[\Override]
            public function getLdapGroupMembersIds($groupDn): array
            {
                return [101, 102];
            }

            #[\Override]
            protected function getDbGroupMembersIds($id): array
            {
                return [];
            }

            #[\Override]
            protected function getDao(): LDAP_ProjectGroupDao
            {
                return $this->dao;
            }
        };
    }
}
