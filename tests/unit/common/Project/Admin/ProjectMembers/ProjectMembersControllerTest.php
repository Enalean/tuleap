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
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\UserRemover;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;

final class ProjectMembersControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ProjectMembersController */
    private $controller;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever */
    private $project_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface */
    private $administrator_checker;
    /** @var M\LegacyMockInterface|M\MockInterface|MembershipDelegationDao */
    private $membership_delegation_dao;

    protected function setUp(): void
    {
        $this->project_retriever         = M::mock(ProjectRetriever::class);
        $this->membership_delegation_dao = M::mock(MembershipDelegationDao::class);
        $this->administrator_checker     = M::mock(ProjectAdministratorChecker::class);
        $this->controller                = new ProjectMembersController(
            M::mock(ProjectMembersDAO::class),
            M::mock(\UserHelper::class),
            M::mock(\UGroupBinding::class),
            M::mock(UserRemover::class),
            M::mock(\EventManager::class),
            M::mock(\UGroupManager::class),
            M::mock(\UserImport::class),
            $this->project_retriever,
            $this->administrator_checker,
            M::mock(SynchronizedProjectMembershipDetector::class),
            $this->membership_delegation_dao
        );
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testNotProjectAdminWithoutDelegationCannotAccessThePage(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with('102')
            ->andReturn($project);
        $current_user = M::mock(PFUser::class)->shouldReceive('getId')
            ->andReturn(110)
            ->getMock();
        $request      = M::mock(HTTPRequest::class)->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user)
            ->getMock();
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->once()
            ->with($current_user, $project)
            ->andThrow(new ForbiddenException());
        $this->membership_delegation_dao->shouldReceive('doesUserHasMembershipDelegation')
            ->once()
            ->with(110, '102')
            ->andReturnFalse();

        $this->expectException(ForbiddenException::class);
        $this->controller->process($request, M::mock(BaseLayout::class), ['id' => '102']);
    }

    public function testItThrowsWhenProjectIsNotActiveAndCurrentUserIsNotSiteAdmin(): void
    {
        $project = M::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $project->shouldReceive('getStatus')
            ->once()
            ->andReturn(\Project::STATUS_SUSPENDED);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with('102')
            ->andReturn($project);
        $current_user = M::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(110);
        $current_user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();
        $request = M::mock(HTTPRequest::class)->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user)
            ->getMock();
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->once()
            ->with($current_user, $project);

        $this->expectException(ForbiddenException::class);
        $this->controller->process($request, M::mock(BaseLayout::class), ['id' => '102']);
    }
}
