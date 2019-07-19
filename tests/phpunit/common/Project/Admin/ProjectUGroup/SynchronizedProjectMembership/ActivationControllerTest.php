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
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Request\NotFoundException;

final class ActivationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ActivationController */
    private $controller;
    /**
     * @var M\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var M\MockInterface|SynchronizedProjectMembershipDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->project_manager = M::mock(\ProjectManager::class);
        $this->dao             = M::mock(SynchronizedProjectMembershipDao::class);
        $this->controller      = new ActivationController($this->project_manager, $this->dao);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testGetUrl(): void
    {
        $project = M::mock(Project::class);
        $project->shouldReceive('getID')
            ->once()
            ->andReturn('104');

        $this->assertEquals(
            '/project/104/admin/change-synchronized-project-membership',
            ActivationController::getUrl($project)
        );
    }

    public function testProcessThrowsNotFoundWhenProjectIsInError(): void
    {
        $project = M::mock(Project::class);
        $project->shouldReceive('isError')
            ->once()
            ->andReturnTrue();
        $variables = ['id' => '104', 'activation' => 'on'];

        $this->project_manager->shouldReceive('getProject')
            ->with('104')
            ->once()
            ->andReturn($project);

        $this->expectException(NotFoundException::class);

        $request = M::mock(\HTTPRequest::class);
        $layout  = M::mock(BaseLayout::class);

        $this->controller->process($request, $layout, $variables);
    }
}
