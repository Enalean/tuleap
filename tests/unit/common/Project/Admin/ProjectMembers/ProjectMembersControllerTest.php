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
use PHPUnit\Framework\MockObject\MockObject;
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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectMembersControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testNotProjectAdminWithoutDelegationCannotAccessThePage(): void
    {
        $project           = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_retriever = $this->createMock(ProjectRetriever::class);
        $project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('102')
            ->willReturn($project);
        $current_user = UserTestBuilder::aUser()
            ->withId(110)
            ->withoutSiteAdministrator()
            ->build();
        $request      = $this->createMock(HTTPRequest::class);
        $request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($current_user);

        $controller = $this->buildController(
            $project_retriever,
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
        );

        self::expectException(ForbiddenException::class);
        $controller->process($request, $this->createMock(BaseLayout::class), ['project_id' => '102']);
    }

    public function testItThrowsWhenProjectIsNotActiveAndCurrentUserIsNotSiteAdmin(): void
    {
        $project           = ProjectTestBuilder::aProject()
            ->withId(102)
            ->withStatusSuspended()
            ->build();
        $project_retriever = $this->createMock(ProjectRetriever::class);
        $project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('102')
            ->willReturn($project);
        $current_user = UserTestBuilder::aUser()
            ->withId(110)
            ->withoutSiteAdministrator()
            ->build();
        $request      = $this->createMock(HTTPRequest::class);
        $request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($current_user);

        $controller = $this->buildController(
            $project_retriever,
            EnsureUserCanManageProjectMembersStub::canManageMembers(),
        );

        self::expectException(ForbiddenException::class);
        $controller->process($request, $this->createMock(BaseLayout::class), ['project_id' => '102']);
    }

    private function buildController(
        ProjectRetriever&MockObject $project_retriever,
        EnsureUserCanManageProjectMembers $members_manager_checker,
    ): ProjectMembersController {
        return new ProjectMembersController(
            $this->createMock(ProjectMembersDAO::class),
            $this->createMock(\UserHelper::class),
            $this->createMock(\UGroupBinding::class),
            $this->createMock(UserRemover::class),
            $this->createMock(\EventManager::class),
            $this->createMock(\UGroupManager::class),
            $this->createMock(\UserImport::class),
            $project_retriever,
            $this->createMock(SynchronizedProjectMembershipDetector::class),
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
