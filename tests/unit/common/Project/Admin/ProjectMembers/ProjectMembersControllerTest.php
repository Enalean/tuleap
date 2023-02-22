<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use HTTPRequest;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\InviteBuddy\InviteBuddiesPresenterBuilder;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\PendingInvitationsForProjectRetrieverStub;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Invitations\CSRFSynchronizerTokenProvider;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\UserRemover;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectMembersControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testNotProjectAdminWithoutDelegationCannotAccessThePage(): void
    {
        $project           = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $project_retriever = M::mock(ProjectRetriever::class);
        $project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with('102')
            ->andReturn($project);
        $current_user = UserTestBuilder::aUser()
            ->withId(110)
            ->withoutSiteAdministrator()
            ->build();
        $request      = M::mock(HTTPRequest::class)->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user)
            ->getMock();

        $controller = $this->buildController(
            $project_retriever,
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
        );

        $this->expectException(ForbiddenException::class);
        $controller->process($request, M::mock(BaseLayout::class), ['project_id' => '102']);
    }

    public function testItThrowsWhenProjectIsNotActiveAndCurrentUserIsNotSiteAdmin(): void
    {
        $project = M::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $project->shouldReceive('getStatus')
            ->once()
            ->andReturn(\Project::STATUS_SUSPENDED);
        $project_retriever = M::mock(ProjectRetriever::class);
        $project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with('102')
            ->andReturn($project);
        $current_user = UserTestBuilder::aUser()
            ->withId(110)
            ->withoutSiteAdministrator()
            ->build();
        $request      = M::mock(HTTPRequest::class)->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user)
            ->getMock();

        $controller = $this->buildController(
            $project_retriever,
            EnsureUserCanManageProjectMembersStub::canManageMembers(),
        );

        $this->expectException(ForbiddenException::class);
        $controller->process($request, M::mock(BaseLayout::class), ['project_id' => '102']);
    }

    private function buildController(
        M\LegacyMockInterface|M\MockInterface|ProjectRetriever $project_retriever,
        EnsureUserCanManageProjectMembers $members_manager_checker,
    ): ProjectMembersController {
        return new ProjectMembersController(
            M::mock(ProjectMembersDAO::class),
            M::mock(\UserHelper::class),
            M::mock(\UGroupBinding::class),
            M::mock(UserRemover::class),
            M::mock(\EventManager::class),
            M::mock(\UGroupManager::class),
            M::mock(\UserImport::class),
            $project_retriever,
            M::mock(SynchronizedProjectMembershipDetector::class),
            $members_manager_checker,
            new ListOfPendingInvitationsPresenterBuilder(
                $this->createStub(InviteBuddyConfiguration::class),
                PendingInvitationsForProjectRetrieverStub::withoutInvitation(),
                $this->createStub(TlpRelativeDatePresenterBuilder::class),
                $this->createMock(CSRFSynchronizerTokenProvider::class),
                $this->createMock(InviteBuddiesPresenterBuilder::class),
            )
        );
    }
}
