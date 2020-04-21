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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use LDAP_UserGroupManager;
use LDAP_GroupManager;

require_once __DIR__ . '/bootstrap.php';

final class UserGroupManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LDAP_UserGroupManager
     */
    private $manager;

    /**
     * @var \LDAP_UserManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $ldap_user_manager;

    /**
     * @var \LDAP_UserGroupDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $ldap_user_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Project
     */
    private $project;

    private $bind_option;

    /**
     * @var string
     */
    private $preserve_option;

    protected function setUp(): void
    {
        parent::setUp();

        $ldap                    = \Mockery::spy(\LDAP::class);
        $this->ldap_user_manager = \Mockery::spy(\LDAP_UserManager::class);
        $this->ldap_user_dao     = \Mockery::spy(\LDAP_UserGroupDao::class);
        $this->project_manager   = \Mockery::spy(\ProjectManager::class);
        $logger                  = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->manager = new LDAP_UserGroupManager(
            $ldap,
            $this->ldap_user_manager,
            $this->ldap_user_dao,
            $this->project_manager,
            $logger,
            new \Tuleap\LDAP\GroupSyncSilentNotificationsManager()
        );

        $this->manager->setProjectId(101);
        $this->manager->setGroupDn('whatever');

        $this->project = \Mockery::spy(\Project::class);
        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($this->project);

        $this->bind_option     = LDAP_GroupManager::BIND_OPTION;
        $this->preserve_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
    }

    public function testItRemovesNonProjectMembersFromUserToAddInPrivateProject(): void
    {
        $this->ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturns(array(
            101 => 101,
            102 => 102
        ));

        $this->ldap_user_dao->shouldReceive('getMembersId')->andReturns(array('101' => '101'));

        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->project->shouldReceive('getMembersId')->andReturns(array(101));

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);

        $this->assertEmpty($users_to_be_added);
    }

    public function testItDoesNotRemoveNonProjectMembersFromUserToAddInPublicProject(): void
    {
        $this->ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturns(array(
            101 => 101,
            102 => 102
        ));

        $this->ldap_user_dao->shouldReceive('getMembersId')->andReturns(array('101' => '101'));

        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('getMembersId')->andReturns(array(101));

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);
        $expected_result   = array(102 => 102);

        $this->assertEquals($expected_result, $users_to_be_added);
    }

    public function testItAddsNonProjectMembersIntoUserToRemoveInPrivateProject(): void
    {
        $this->ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturns(array(
            101 => 101,
            102 => 102
        ));

        $this->ldap_user_dao->shouldReceive('getMembersId')->andReturns(array(
            '101' => '101',
            '102' => '102'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->project->shouldReceive('getMembersId')->andReturns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);
        $expected_result     = array(102 => 102);

        $this->assertEquals($expected_result, $users_to_be_removed);
    }

    public function testItDoesNotAddNonProjectMembersIntoUserToRemoveInPublicProject(): void
    {
        $this->ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturns(array(
            101 => 101,
            102 => 102
        ));

        $this->ldap_user_dao->shouldReceive('getMembersId')->andReturns(array(
            '101' => '101',
        ));

        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('getMembersId')->andReturns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);

        $this->assertEmpty($users_to_be_removed);
    }

    public function testItAddsNonProjectMembersIntoUserToRemoveInPrivateProjectEvenWithPreserveMembers(): void
    {
        $this->ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturns(array(
            101 => 101,
            102 => 102
        ));

        $this->ldap_user_dao->shouldReceive('getMembersId')->andReturns(array(
            '101' => '101',
            '102' => '102'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->project->shouldReceive('getMembersId')->andReturns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->preserve_option);
        $expected_result     = array(102 => 102);

        $this->assertEquals($expected_result, $users_to_be_removed);
    }
}
