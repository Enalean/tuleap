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

use Mockery as M;
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
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var MemberRemovalController
     */
    private $controller;
    /**
     * @var \HTTPRequest|M\MockInterface
     */
    private $http_request;
    /**
     * @var M\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var M\MockInterface|MemberRemover
     */
    private $member_remover;
    /**
     * @var M\MockInterface|ProjectMemberRemover
     */
    private $project_member_remover;
    /**
     * @var \CSRFSynchronizerToken|M\MockInterface
     */
    private $csrf;

    protected function setUp(): void
    {
        $this->project_retriever      = M::mock(ProjectRetriever::class);
        $this->administrator_checker  = M::mock(ProjectAdministratorChecker::class);
        $this->ugroup_manager         = M::mock(\UGroupManager::class);
        $this->user_manager           = M::mock(\UserManager::class);
        $this->member_remover         = M::mock(MemberRemover::class);
        $this->http_request           = M::mock(\HTTPRequest::class);
        $this->layout                 = M::mock(BaseLayout::class);
        $this->project_member_remover = M::mock(ProjectMemberRemover::class);
        $this->csrf                   = M::mock(\CSRFSynchronizerToken::class);
        $this->csrf->shouldReceive('check')->once();
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
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($project_admin);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($project_admin, $project)
            ->once();

        return $project_admin;
    }

    public function testItRemovesWithSuccess(): void
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $project_admin = $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->http_request->shouldReceive('get')->with('remove-from-ugroup')->andReturn('remove-from-ugroup-only');

        $this->member_remover->shouldReceive('removeMember')->with($user_to_remove, $project_admin, $ugroup);

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesFromUserGroupOnlyWithError()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $project_admin = $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->http_request->shouldReceive('get')->with('remove-from-ugroup')->andReturn('remove-from-ugroup-only');

        $this->member_remover->shouldReceive('removeMember')->with($user_to_remove, $project_admin, $ugroup)->andThrow(new CannotModifyBoundGroupException());

        $this->layout->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any());

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesFromUserGroupAndProject()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = M::mock(\PFUser::class, ['getId' => 303, 'isAdmin' => false]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->http_request->shouldReceive('get')->with('remove-from-ugroup')->andReturn('remove-from-ugroup-and-project');

        $this->project_member_remover->shouldReceive('removeUserFromProject')->with(101, 303)->once();

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntRemoveProjectAdminsFromUserGroupAndProject(): void
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = M::mock(\PFUser::class, ['getId' => 303]);
        $user_to_remove->shouldReceive('isAdmin')->with(101)->andReturnTrue();
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->http_request->shouldReceive('get')->with('remove-from-ugroup')->andReturn('remove-from-ugroup-and-project');

        $this->project_member_remover->shouldNotReceive('removeUserFromProject');

        $this->layout->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any());
        $exception_stop_exec_redirect = new \Exception("Redirect");
        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))->andThrow($exception_stop_exec_redirect);

        $this->expectExceptionObject($exception_stop_exec_redirect);
        $this->controller->process($this->http_request, $this->layout, ['project_id' => '101', 'user-group-id' => '202']);
    }
}
