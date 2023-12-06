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
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\UserTestBuilder;

final class MemberAdditionControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private \UGroupManager&MockObject $ugroup_manager;
    private \UserManager&MockObject $user_manager;
    private MemberAdditionController $controller;
    private \HTTPRequest&MockObject $http_request;
    private BaseLayout&MockObject $layout;
    private \CSRFSynchronizerToken&MockObject $csrf;
    private MemberAdder&MockObject $member_adder;
    private \PFUser $project_admin;

    protected function setUp(): void
    {
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);
        $this->ugroup_manager        = $this->createMock(\UGroupManager::class);
        $this->user_manager          = $this->createMock(\UserManager::class);
        $this->member_adder          = $this->createMock(MemberAdder::class);
        $this->http_request          = $this->createMock(\HTTPRequest::class);
        $this->layout                = $this->createMock(BaseLayout::class);
        $this->csrf                  = $this->createMock(\CSRFSynchronizerToken::class);
        $this->csrf->expects(self::once())->method('check');
        $this->controller = new MemberAdditionController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->ugroup_manager,
            $this->user_manager,
            $this->member_adder,
            $this->csrf
        );

        $this->project_admin = UserTestBuilder::buildWithDefaults();
    }

    private function checkUserIsProjectAdmin(\Project $project): void
    {
        $this->http_request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->project_admin);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($this->project_admin, $project);
    }

    public function testItAddsUserWithSuccess()
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
        $ugroup->method('isBound')->willReturn(false);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $user_to_add = new \PFUser(['user_id' => 303]);
        $this->http_request->method('get')->with('add_user_name')->willReturn('danton');
        $this->user_manager->method('findUser')->with('danton')->willReturn($user_to_add);

        $this->member_adder->expects(self::once())->method('addMember')->with($user_to_add, $ugroup, $this->project_admin);

        $this->layout->expects(self::once())->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntAddInBoundGroups(): void
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
        $ugroup->method('isBound')->willReturn(true);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $exception_stop_exec_redirect = new \Exception("Redirect");
        $this->layout->expects(self::once())->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))
            ->willThrowException($exception_stop_exec_redirect);

        self::expectExceptionObject($exception_stop_exec_redirect);
        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntAddInvalidUser(): void
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
        $ugroup->method('isBound')->willReturn(false);
        $this->ugroup_manager->method('getUGroup')->with($project, '202')->willReturn($ugroup);

        $this->http_request->method('get')->with('add_user_name')->willReturn('danton');
        $this->user_manager->method('findUser')->with('danton')->willReturn(null);

        $this->layout->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR, self::anything());
        $exception_stop_exec_redirect = new \Exception("Redirect");
        $this->layout->expects(self::once())->method('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))
            ->willThrowException($exception_stop_exec_redirect);

        self::expectExceptionObject($exception_stop_exec_redirect);
        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }
}
