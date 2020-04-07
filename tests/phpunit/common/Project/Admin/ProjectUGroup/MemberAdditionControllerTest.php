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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Request\ProjectRetriever;

final class MemberAdditionControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

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
     * @var MemberAdditionController
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
     * @var \CSRFSynchronizerToken|M\MockInterface
     */
    private $csrf;
    /**
     * @var M\MockInterface|MemberAdder
     */
    private $member_adder;

    protected function setUp(): void
    {
        $this->project_retriever = M::mock(ProjectRetriever::class);
        $this->administrator_checker = M::mock(ProjectAdministratorChecker::class);
        $this->ugroup_manager    = M::mock(\UGroupManager::class);
        $this->user_manager      = M::mock(\UserManager::class);
        $this->member_adder      = M::mock(MemberAdder::class);
        $this->http_request      = M::mock(\HTTPRequest::class);
        $this->layout            = M::mock(BaseLayout::class);
        $this->csrf              = M::mock(\CSRFSynchronizerToken::class);
        $this->csrf->shouldReceive('check')->once();
        $this->controller = new MemberAdditionController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->ugroup_manager,
            $this->user_manager,
            $this->member_adder,
            $this->csrf
        );
    }

    private function checkUserIsProjectAdmin(\Project $project): void
    {
        $project_admin = M::mock(\PFUser::class);
        $this->http_request->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($project_admin);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($project_admin, $project)
            ->once();
    }

    public function testItAddsUserWithSuccess()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202, 'isBound' => false]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_add = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('add_user_name')->andReturn('danton');
        $this->user_manager->shouldReceive('findUser')->with('danton')->andReturn($user_to_add);

        $this->member_adder->shouldReceive('addMember')->with($user_to_add, $ugroup)->once();

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))->once();

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntAddInBoundGroups()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202, 'isBound' => true]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))->once();

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }

    public function testItDoesntAddInvalidUser()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($project);

        $this->checkUserIsProjectAdmin($project);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProjectId' => 101, 'getId' => 202, 'isBound' => false]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $this->http_request->shouldReceive('get')->with('add_user_name')->andReturn('danton');
        $this->user_manager->shouldReceive('findUser')->with('danton')->andReturnNull();

        $this->layout->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();
        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup))->once();

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }
}
