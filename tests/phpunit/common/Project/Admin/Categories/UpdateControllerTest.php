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
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;

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
    private $project_admin;
    /**
     * @var UpdateController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAdministratorChecker
     */
    private $administrator_checker;
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
        $this->request               = Mockery::mock(HTTPRequest::class);
        $this->layout                = Mockery::mock(BaseLayout::class);
        $this->project_admin         = Mockery::mock(PFUser::class);
        $this->project_retriever     = Mockery::mock(ProjectRetriever::class);
        $this->administrator_checker = Mockery::mock(ProjectAdministratorChecker::class);
        $this->updater               = Mockery::mock(ProjectCategoriesUpdater::class);
        $this->project               = Mockery::mock(Project::class);

        $this->project->shouldReceive('getID')->andReturn(42);
        $this->project->shouldReceive('isError')->andReturn(false);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('42')
            ->once()
            ->andReturn($this->project);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')->once();
        $this->project_admin->shouldReceive('isAdmin')->with(42)->andReturn(true);

        $this->controller = new UpdateController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->updater
        );
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
