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
use GitPermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Git\AsynchronousEvents\RefreshGitoliteProjectConfigurationTask;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitPermissionsManagerTest extends TestCase
{
    private GitPermissionsManager $git_permissions_manager;
    private Git_PermissionsDao&MockObject $git_permissions_dao;
    private EnqueueTaskStub $enqueuer;
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->git_permissions_dao     = $this->createMock(Git_PermissionsDao::class);
        $this->enqueuer                = new EnqueueTaskStub();
        $fine_grained_dao              = $this->createStub(FineGrainedDao::class);
        $this->git_permissions_manager = new GitPermissionsManager(
            $this->git_permissions_dao,
            $this->enqueuer,
            $fine_grained_dao,
            $this->createStub(FineGrainedRetriever::class),
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

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(101), new RefreshGitoliteProjectConfigurationTask(104)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromAnonymousToRegularItDoesNothingWhenNoProjectsWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithAnonymousRepositories')->willReturn([]);
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromRegularToAnonymousItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithAnonymousRepositories');
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithUnrestrictedRepositories');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::ANONYMOUS);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromAnonymousToRestrictedItUpdatesAllProjectsThatWereUsingAnonymous(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithAnonymousRepositories')->willReturn([['group_id' => 101], ['group_id' => 104]]);

        $this->git_permissions_dao->expects($this->once())->method('updateAllAnonymousAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::RESTRICTED);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(101), new RefreshGitoliteProjectConfigurationTask(104)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromRestrictedToAnonymousItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([['group_id' => 102], ['group_id' => 107]]);

        $this->git_permissions_dao->expects($this->once())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102), new RefreshGitoliteProjectConfigurationTask(107)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromRestrictedToRegularItUpdatesAllProjectThatWereUsingUnRestricted(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([['group_id' => 102], ['group_id' => 107]]);

        $this->git_permissions_dao->expects($this->once())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102), new RefreshGitoliteProjectConfigurationTask(107)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromRestrictedToRegularItDoesNothingWhenNoProjectsWereUsingAuthenticated(): void
    {
        $this->git_permissions_dao->method('getAllProjectsWithUnrestrictedRepositories')->willReturn([]);

        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromRegularToRestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithAnonymousRepositories');
        $this->git_permissions_dao->expects($this->never())->method('getAllProjectsWithUnrestrictedRepositories');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAnonymousAccessToRegistered');
        $this->git_permissions_dao->expects($this->never())->method('updateAllAuthenticatedAccessToRegistered');

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::RESTRICTED);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromPublicToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAnonymousRegisteredAuthenticated')->with(102);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromPublicToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PUBLIC_UNRESTRICTED);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromPrivateToPublicItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromPrivateToUnrestrictedItDoesNothing(): void
    {
        $this->git_permissions_dao->expects($this->never())->method('disableAnonymousRegisteredAuthenticated');

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromUnrestrictedToPublicItRemoveAccessToAuthenticated(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAuthenticated')->with(102);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102)], $this->enqueuer->queued_tasks);
    }

    public function testWhenSwitchingFromUnrestrictedToPrivateItSetsProjectMembersForAllPublicRepositories(): void
    {
        $this->git_permissions_dao->expects($this->once())->method('disableAnonymousRegisteredAuthenticated')->with(102);

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE);

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102)], $this->enqueuer->queued_tasks);
    }
}
