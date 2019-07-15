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
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;

class MemberRemovalControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalLanguageMock, GlobalResponseMock;

    /**
     * @var M\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\MockInterface|DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var M\MockInterface|StaticMemberRemover
     */
    private $static_member_remover;
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
     * @var M\MockInterface|\PFUser
     */
    private $a_user;

    protected function setUp(): void
    {
        $this->project_manager                = M::mock(\ProjectManager::class);
        $this->ugroup_manager                 = M::mock(\UGroupManager::class);
        $this->user_manager                   = M::mock(\UserManager::class);
        $this->dynamic_ugroup_members_updater = M::mock(DynamicUGroupMembersUpdater::class);
        $this->static_member_remover          = M::mock(StaticMemberRemover::class);
        $this->http_request                   = M::mock(\HTTPRequest::class);
        $this->layout                         = M::mock(BaseLayout::class);
        $this->a_user                         = M::mock(\PFUser::class);
        $this->controller = new MemberRemovalController($this->project_manager, $this->ugroup_manager, $this->user_manager, $this->dynamic_ugroup_members_updater, $this->static_member_remover);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItRemovesFromDynamicUGroup()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->with('101')->andReturn($project);

        $this->a_user->shouldReceive('isAdmin')->with(101)->andReturnTrue();
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($this->a_user);

        $ugroup = M::mock(\ProjectUGroup::class, [ 'getProjectId' => 101, 'getId' => 202, 'isBound' => false, 'isStatic' => false ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->static_member_remover->shouldNotReceive('removeUser');

        $this->dynamic_ugroup_members_updater->shouldReceive('removeUser')->with($project, $ugroup, $user_to_remove);

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesFromStaticUGroup()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->with('101')->andReturn($project);

        $this->a_user->shouldReceive('isAdmin')->with(101)->andReturnTrue();
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($this->a_user);

        $ugroup = M::mock(\ProjectUGroup::class, [ 'getProjectId' => 101, 'getId' => 202, 'isBound' => false, 'isStatic' => true ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->static_member_remover->shouldReceive('removeUser')->with($ugroup, $user_to_remove);

        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }

    public function testItRemovesNothingFromBoundGroup()
    {
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->with('101')->andReturn($project);

        $this->a_user->shouldReceive('isAdmin')->with(101)->andReturnTrue();
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($this->a_user);

        $ugroup = M::mock(\ProjectUGroup::class, [ 'getProjectId' => 101, 'getId' => 202, 'isBound' => true]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, '202')->andReturn($ugroup);

        $user_to_remove = new \PFUser(['user_id' => 303]);
        $this->http_request->shouldReceive('get')->with('remove_user')->andReturn('303');
        $this->user_manager->shouldReceive('getUserById')->with('303')->andReturn($user_to_remove);

        $this->static_member_remover->shouldNotReceive('removeUser')->with($ugroup, $user_to_remove);
        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->layout->shouldReceive('addFeedback');

        $this->layout->shouldReceive('redirect')->with(UGroupRouter::getUGroupUrl($ugroup));

        $this->controller->process($this->http_request, $this->layout, ['id' => '101', 'user-group-id' => '202']);
    }
}
