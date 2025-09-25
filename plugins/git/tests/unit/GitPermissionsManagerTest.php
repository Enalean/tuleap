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

declare(strict_types=1);

namespace Tuleap\Git;

use ForgeAccess;
use Git_PermissionsDao;
use Git_SystemEventManager;
use GitPermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitPermissionsManagerTest extends TestCase
{
    private GitPermissionsManager $git_permissions_manager;
    private Git_PermissionsDao&MockObject $git_permissions_dao;
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->git_permissions_dao      = $this->createMock(Git_PermissionsDao::class);
        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $fine_grained_dao               = $this->createMock(FineGrainedDao::class);
        $this->git_permissions_manager  = new GitPermissionsManager(
            $this->git_permissions_dao,
            $this->git_system_event_manager,
            $fine_grained_dao,
            $this->createMock(FineGrainedRetriever::class),
        );
        $fine_grained_dao->method('updateAllAnonymousAccessToRegistered');
        $fine_grained_dao->method('updateAllAuthenticatedAccessToRegistered');
        $fine_grained_dao->method('disableAuthenticated');
        $fine_grained_dao->method('disableAnonymousRegisteredAuthenticated');

        $this->project = ProjectTestBuilder::aProject()->withId(102)->withAccessPrivate()->build();
    }

    public function testWhenSwitchingFromAnonymousToRegularItUpdatesAllProjectsThatWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithAnonymousRepositories')->willReturn([['group_id' => 101], ['group_id' => 104]]);
        $this->git_permissions_dao->expects($this->once())->method('updateAllAnonymousAccessToRegistered');
        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([101, 104]);

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromAnonymousToRegularItDoesNothingWhenNoProjectsWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithAnonymousRepositories')->willReturn([]);
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToAnonymousItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithAnonymousRepositories');
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithUnrestrictedRepositories');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromAnonymousToRestrictedItUpdatesAllProjectsThatWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithAnonymousRepositories')->willReturn([['group_id' => 101], ['group_id' => 104]]);

        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([101, 104]);
        $this->git_permissions_dao->expects($this->once())->method('updateAllAnonymousAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromRestrictedToAnonymousItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([['group_id' => 102], ['group_id' => 107]]);

        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([102, 107]);
        $this->git_permissions_dao->expects($this->once())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromRestrictedToRegularItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([['group_id' => 102], ['group_id' => 107]]);

        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([102, 107]);
        $this->git_permissions_dao->expects($this->once())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRestrictedToRegularItDoesNothingWhenNoProjectsWereUsingAuthenticated(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([]);

        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToRestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithAnonymousRepositories');
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithUnrestrictedRepositories');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromPublicToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAnonymousRegisteredAuthenticated')->with(102);
        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([102]);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE);
    }

    public function testWhenSwitchingFromPublicToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PUBLIC_UNRESTRICTED);
    }

    public function testWhenSwitchingFromPrivateToPublicItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromPrivateToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');
        $this->git_system_event_manager->expects($this->never())->method('queueProjectsConfigurationUpdate');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPublicItRemoveAccessToAuthenticated(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAuthenticated')->with(102);
        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([102]);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAnonymousRegisteredAuthenticated')->with(102);
        $this->git_system_event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with([102]);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE);
    }
}
