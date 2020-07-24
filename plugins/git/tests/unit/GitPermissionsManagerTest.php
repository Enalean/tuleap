<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use Tuleap\Git\Permissions\FineGrainedDao;

require_once 'bootstrap.php';

final class GitPermissionsManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $permissions_manager;
    protected $git_permissions_manager;
    protected $git_permissions_dao;
    protected $git_system_event_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);
        $this->git_permissions_dao      = Mockery::mock(Git_PermissionsDao::class);
        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->git_permissions_manager  = new GitPermissionsManager(
            $this->git_permissions_dao,
            $this->git_system_event_manager,
            \Mockery::spy(FineGrainedDao::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class)
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => 102, 'getUnixName' => false, 'isPublic' => false]);
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function testWhenSwitchingFromAnonymousToRegularItUpdatesAllProjectsThatWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithAnonymousRepositories')->andReturns([['group_id' => 101], ['group_id' => 104]]);

        $this->git_permissions_dao->shouldReceive('updateAllAnonymousAccessToRegistered')->once();

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([101, 104])->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromAnonymousToRegularItDoesNothingWhenNoProjectsWereUsingAnonymous()
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithAnonymousRepositories')->andReturns([]);

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAnonymousAccessToRegistered')->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToAnonymousItDoesNothing(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithAnonymousRepositories')->never();
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithUnrestrictedRepositories')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAnonymousAccessToRegistered')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAuthenticatedAccessToRegistered')->never();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromAnonymousToRestrictedItUpdatesAllProjectsThatWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithAnonymousRepositories')->andReturns([['group_id' => 101], ['group_id' => 104]]);

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([101, 104])->once();
        $this->git_permissions_dao->shouldReceive('updateAllAnonymousAccessToRegistered')->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromRestrictedToAnonymousItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithUnrestrictedRepositories')->andReturns([['group_id' => 102], ['group_id' => 107]]);

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([102, 107])->once();
        $this->git_permissions_dao->shouldReceive('updateAllAuthenticatedAccessToRegistered')->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromRestrictedToRegularItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithUnrestrictedRepositories')->andReturns([['group_id' => 102], ['group_id' => 107]]);

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([102, 107])->once();
        $this->git_permissions_dao->shouldReceive('updateAllAuthenticatedAccessToRegistered')->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRestrictedToRegularItDoesNothingWhenNoProjectsWereUsingAuthenticated(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithUnrestrictedRepositories')->andReturns([]);

        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAuthenticatedAccessToRegistered')->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToRestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithAnonymousRepositories')->never();
        $this->git_permissions_dao->shouldReceive('getAllProjectsWithUnrestrictedRepositories')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAnonymousAccessToRegistered')->never();
        $this->git_permissions_dao->shouldReceive('updateAllAuthenticatedAccessToRegistered')->never();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromPublicToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAnonymousRegisteredAuthenticated')->with(102)->once();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([102])->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE);
    }

    public function testWhenSwitchingFromPublicToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAnonymousRegisteredAuthenticated')->never();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PUBLIC_UNRESTRICTED);
    }

    public function testWhenSwitchingFromPrivateToPublicItDoesNothing(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAnonymousRegisteredAuthenticated')->never();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromPrivateToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAnonymousRegisteredAuthenticated')->never();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPublicItRemoveAccessToAuthenticated(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAuthenticated')->with(102)->once();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([102])->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->shouldReceive('disableAnonymousRegisteredAuthenticated')->with(102)->once();
        $this->git_system_event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with([102])->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE);
    }
}
