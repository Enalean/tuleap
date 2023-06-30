<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use LDAP_UserGroupManager;
use LDAP_GroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

final class UserGroupManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LDAP_UserGroupManager $manager;
    private \LDAP_UserManager&MockObject $ldap_user_manager;
    private \LDAP_UserGroupDao&MockObject $ldap_user_dao;
    private \ProjectManager&MockObject $project_manager;
    private \Project&MockObject $project;
    private string $bind_option;
    private string $preserve_option;

    protected function setUp(): void
    {
        parent::setUp();

        $ldap                    = $this->createMock(\LDAP::class);
        $this->ldap_user_manager = $this->createMock(\LDAP_UserManager::class);
        $this->ldap_user_dao     = $this->createMock(\LDAP_UserGroupDao::class);
        $this->project_manager   = $this->createMock(\ProjectManager::class);

        $ldap->method('searchGroupMembers')->willReturn(false);

        $this->manager = new LDAP_UserGroupManager(
            $ldap,
            $this->ldap_user_manager,
            $this->ldap_user_dao,
            $this->project_manager,
            new NullLogger(),
            new \Tuleap\LDAP\GroupSyncSilentNotificationsManager()
        );

        $this->manager->setProjectId(101);
        $this->manager->setGroupDn('whatever');

        $this->project = $this->createMock(\Project::class);
        $this->project_manager->method('getProject')->with(101)->willReturn($this->project);

        $this->bind_option     = LDAP_GroupManager::BIND_OPTION;
        $this->preserve_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
    }

    public function testItRemovesNonProjectMembersFromUserToAddInPrivateProject(): void
    {
        $this->ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([
            101 => 101,
            102 => 102,
        ]);

        $this->ldap_user_dao->method('getMembersId')->willReturn(['101' => '101']);

        $this->project->method('isPublic')->willReturn(false);
        $this->project->method('getMembersId')->willReturn([101]);

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);

        self::assertEmpty($users_to_be_added);
    }

    public function testItDoesNotRemoveNonProjectMembersFromUserToAddInPublicProject(): void
    {
        $this->ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([
            101 => 101,
            102 => 102,
        ]);

        $this->ldap_user_dao->method('getMembersId')->willReturn(['101' => '101']);

        $this->project->method('isPublic')->willReturn(true);
        $this->project->method('getMembersId')->willReturn([101]);

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);
        $expected_result   = [102 => 102];

        self::assertEquals($expected_result, $users_to_be_added);
    }

    public function testItAddsNonProjectMembersIntoUserToRemoveInPrivateProject(): void
    {
        $this->ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([
            101 => 101,
            102 => 102,
        ]);

        $this->ldap_user_dao->method('getMembersId')->willReturn([
            '101' => '101',
            '102' => '102',
        ]);

        $this->project->method('isPublic')->willReturn(false);
        $this->project->method('getMembersId')->willReturn([101]);

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);
        $expected_result     = [102 => 102];

        self::assertEquals($expected_result, $users_to_be_removed);
    }

    public function testItDoesNotAddNonProjectMembersIntoUserToRemoveInPublicProject(): void
    {
        $this->ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([
            101 => 101,
            102 => 102,
        ]);

        $this->ldap_user_dao->method('getMembersId')->willReturn([
            '101' => '101',
        ]);

        $this->project->method('isPublic')->willReturn(true);
        $this->project->method('getMembersId')->willReturn([101]);

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);

        self::assertEmpty($users_to_be_removed);
    }

    public function testItAddsNonProjectMembersIntoUserToRemoveInPrivateProjectEvenWithPreserveMembers(): void
    {
        $this->ldap_user_manager->method('getUserIdsForLdapUser')->willReturn([
            101 => 101,
            102 => 102,
        ]);

        $this->ldap_user_dao->method('getMembersId')->willReturn([
            '101' => '101',
            '102' => '102',
        ]);

        $this->project->method('isPublic')->willReturn(false);
        $this->project->method('getMembersId')->willReturn([101]);

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->preserve_option);
        $expected_result     = [102 => 102];

        self::assertEquals($expected_result, $users_to_be_removed);
    }
}
