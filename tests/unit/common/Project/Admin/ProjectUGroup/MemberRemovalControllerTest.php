<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\Membership\CannotModifyBoundGroupException;
use Tuleap\Project\UGroups\Membership\MemberRemover;
use Tuleap\Project\UserRemover as ProjectMemberRemover;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\UserTestBuilder;

final class MemberRemovalControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private \UGroupManager&MockObject $ugroup_manager;
    private \UserManager&MockObject $user_manager;
    private MemberRemovalController $controller;
    private \HTTPRequest&MockObject $http_request;
    private BaseLayout&MockObject $layout;
    private MemberRemover&MockObject $member_remover;
    private ProjectMemberRemover&MockObject $project_member_remover;
    private \CSRFSynchronizerToken&MockObject $csrf;

    protected function setUp(): void
    {
        $this->project_retriever      = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker  = $this->createMock(ProjectAdministratorChecker::class);
        $this->ugroup_manager         = $this->createMock(\UGroupManager::class);
        $this->user_manager           = $this->createMock(\UserManager::class);
        $this->member_remover         = $this->createMock(MemberRemover::class);
        $this->http_request           = $this->createMock(\HTTPRequest::class);
        $this->layout                 = $this->createMock(BaseLayout::class);
        $this->project_member_remover = $this->createMock(ProjectMemberRemover::class);
        $this->csrf                   = $this->createMock(\CSRFSynchronizerToken::class);
        $this->csrf->expects(self::once())->method('check');
        $this->controller = new MemberRemovalController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->ugroup_manager,
            $this->user_manager,
            $this->member_remover,
            $this->project_member_remover,
            $this->csrf
        );
    }

    private function checkUserIsProjectAdmin(\Project $project): \PFUser
    {
        $project_admin = UserTestBuilder::aUser()->build();
        $this->http_request->method('getCurrentUser')->willReturn($project_admin);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($project_admin, $project);

        return $project_admin;
    }

    public function testItRemovesWithSuccess(): void
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('101')
            ->willReturn($project);

        $project_admin = $this->checkUserIsProjectAdmin($project);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(101);
        $ugroup->method('getId')->willReturn(202);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->method('get')
            ->withConsecutive(
                ['remove_user'],
                ['remove-from-ugroup']
            )->willReturnOnConsecutiveCalls('303', 'remove-from-ugroup-only');
        $this->user_manager->method('getUserById')->with('303')->willReturn($user_to_remove);

        $this->member_remover->method('removeMember')->with($user_to_remove, $project_admin, $ugroup);

        $this->layout->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesFromUserGroupOnlyWithError()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('101')
            ->willReturn($project);

        $project_admin = $this->checkUserIsProjectAdmin($project);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(101);
        $ugroup->method('getId')->willReturn(202);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->method('get')
            ->withConsecutive(
                ['remove_user'],
                ['remove-from-ugroup']
            )->willReturnOnConsecutiveCalls('303', 'remove-from-ugroup-only');
        $this->user_manager->method('getUserById')->with('303')->willReturn($user_to_remove);

        $this->member_remover->method('removeMember')->with($user_to_remove, $project_admin, $ugroup)
            ->willThrowException(new CannotModifyBoundGroupException());

        $this->layout->method('addFeedback')->with(\Feedback::ERROR, self::anything());

        $this->layout->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesFromUserGroupAndProject()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('101')
            ->willReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(101);
        $ugroup->method('getId')->willReturn(202);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $user_to_remove = UserTestBuilder::aUser()
            ->withId(303)
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();
        $this->http_request->method('get')
            ->withConsecutive(
                ['remove_user'],
                ['remove-from-ugroup']
            )->willReturnOnConsecutiveCalls('303', 'remove-from-ugroup-and-project');
        $this->user_manager->method('getUserById')->with('303')->willReturn($user_to_remove);

        $this->project_member_remover->expects(self::once())->method('removeUserFromProject')->with(101, 303);

        $this->layout->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntRemoveProjectAdminsFromUserGroupAndProject(): void
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('101')
            ->willReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(101);
        $ugroup->method('getId')->willReturn(202);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $user_to_remove = UserTestBuilder::aUser()
            ->withId(303)
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();
        $this->http_request->method('get')
            ->withConsecutive(
                ['remove_user'],
                ['remove-from-ugroup']
            )->willReturnOnConsecutiveCalls('303', 'remove-from-ugroup-and-project');
        $this->user_manager->method('getUserById')->with('303')->willReturn($user_to_remove);

        $this->project_member_remover->method('removeUserFromProject');

        $this->layout->method('addFeedback')->with(\Feedback::ERROR, self::anything());
        $exception_stop_exec_redirect = new \Exception("Redirect");
        $this->layout->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))->willThrowException($exception_stop_exec_redirect);

        self::expectExceptionObject($exception_stop_exec_redirect);
        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }
}
