<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Categories;

use Feedback;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class UpdateControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|\HTTPRequest
     */
    private $request;
    /**
     * @var Mockery\MockInterface|\PFUser
     */
    private $project_member;
    /**
     * @var Mockery\MockInterface|\PFUser
     */
    private $project_admin;
    /**
     * @var UpdateController
     */
    private $controller;
    /**
     * @var Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\MockInterface|ProjectCategoriesUpdater
     */
    private $updater;
    /**
     * @var Mockery\MockInterface|BaseLayout
     */
    private $layout;

    /** @before */
    public function instantiateMocks(): void
    {
        $this->request         = Mockery::mock(HTTPRequest::class);
        $this->layout          = Mockery::mock(BaseLayout::class);
        $this->project_member  = Mockery::mock(PFUser::class);
        $this->project_admin   = Mockery::mock(PFUser::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->updater         = Mockery::mock(ProjectCategoriesUpdater::class);
        $this->project         = Mockery::mock(Project::class);

        $this->project->shouldReceive('getID')->andReturn(42);
        $this->project->shouldReceive('isError')->andReturn(false);
        $this->project_manager->shouldReceive('getProject')->with('42')->andReturn($this->project);
        $this->project_admin->shouldReceive('isAdmin')->with(42)->andReturn(true);
        $this->project_member->shouldReceive('isAdmin')->with(42)->andReturn(false);

        $this->controller = new UpdateController($this->project_manager, $this->updater);
    }

    public function testThrowsExceptionWhenProjectIsNotFound(): void
    {
        $not_found_project = Mockery::mock(Project::class);
        $not_found_project->shouldReceive('isError')->andReturn(true);

        $this->project_manager->shouldReceive('getProject')->with('unknown')->andReturn($not_found_project);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['id' => 'unknown']);
    }

    public function testThrowsExceptionWhenUserIsNotProjectAdmin(): void
    {
        $this->request->shouldReceive('getCurrentUser')->andReturn($this->project_member);

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->layout, ['id' => '42']);
    }

    public function testItDisplaysAnErrorIfCategoriesIsNotAnArray(): void
    {
        $this->request->shouldReceive('getCurrentUser')->andReturn($this->project_admin);
        $this->request->shouldReceive('get')->with('categories')->andReturn('string');

        $this->layout->shouldReceive('addFeedback')->with(Feedback::ERROR, Mockery::any());
        $this->layout->shouldReceive('redirect')->with('/project/42/admin/categories');

        $this->controller->process($this->request, $this->layout, ['id' => '42']);
    }
}
