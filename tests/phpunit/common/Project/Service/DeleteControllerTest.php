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

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Mockery as M;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Service;
use ServiceDao;
use ServiceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;

class DeleteControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var DeleteController
     */
    private $controller;
    /**
     * @var M\MockInterface|ServiceDao
     */
    private $service_dao;
    /**
     * @var M\MockInterface|ProjectManager
     */
    private $project_manger;
    /**
     * @var CSRFSynchronizerToken|M\MockInterface
     */
    private $csrf_token;
    /**
     * @var M\MockInterface|ServiceManager
     */
    private $service_manager;
    /**
     * @var HTTPRequest|M\MockInterface
     */
    private $request;
    /**
     * @var M\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var M\MockInterface|Project
     */
    private $project;
    /**
     * @var int
     */
    private $project_id;
    /**
     * @var M\MockInterface|PFUser
     */
    private $project_admin;
    private $service_id;
    private $default_template_project;

    protected function setUp(): void
    {
        $this->service_dao     = M::mock(ServiceDao::class);
        $this->project_admin   = M::mock(PFUser::class);
        $this->service_manager = M::mock(ServiceManager::class);

        $this->service_id = '14';

        $this->project_id      = 120;
        $this->project         = M::mock(Project::class, ['getID' => (string) $this->project_id, 'isError' => false]);
        $this->project_manger  = M::mock(ProjectManager::class);
        $this->project_manger->shouldReceive('getProject')->with((string) $this->project_id)->andReturn($this->project);
        $this->project_admin->shouldReceive('isAdmin')->with((string) $this->project_id)->andReturnTrue();

        $this->default_template_project = M::mock(Project::class, ['getID' => (string) Project::ADMIN_PROJECT_ID, 'isError' => false]);
        $this->project_manger->shouldReceive('getProject')->with((string) Project::ADMIN_PROJECT_ID)->andReturn($this->default_template_project);
        $this->project_admin->shouldReceive('isAdmin')->with((string) Project::ADMIN_PROJECT_ID)->andReturnTrue();

        $this->csrf_token      = M::mock(CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->once()->byDefault();
        $this->request         = M::mock(HTTPRequest::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($this->project_admin)->byDefault();
        $this->layout          = M::mock(BaseLayout::class);
        $this->request->shouldReceive('getValidated')->with('service_id', M::andAnyOtherArgs())->andReturn($this->service_id)->byDefault();

        $this->controller = new DeleteController($this->service_dao, $this->project_manger, $this->csrf_token, $this->service_manager);
    }

    public function testItDeletesOneService(): void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->shouldReceive('delete')->with($this->project_id, $this->service_id)->andReturnTrue();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::INFO, M::any())->once();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['id' => '120']);
    }

    public function testItDeletesAllServicesWhenItsInDefaultTemplate(): void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->default_template_project, ['service_id' => $this->service_id, 'short_name' => 'homepage', 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->shouldReceive('delete')->with((string) Project::ADMIN_PROJECT_ID, $this->service_id)->andReturnTrue();
        $this->service_dao->shouldReceive('deleteFromAllProjects')->with('homepage')->once()->andReturnTrue();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::INFO, M::any())->twice();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['id' => (string) Project::ADMIN_PROJECT_ID]);
    }

    public function testItRedirectNonAdminUsers(): void
    {
        $random_user = M::mock(PFUser::class);
        $random_user->shouldReceive('isAdmin')->with((string) $this->project_id)->andReturnFalse();
        $this->request->shouldReceive('getCurrentUser')->andReturn($random_user);

        $this->csrf_token->shouldNotReceive('check');
        $this->service_dao->shouldNotReceive('delete');
        $this->service_dao->shouldNotReceive('deleteFromAllProjects');

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->layout, ['id' => '120']);
    }

    public function testItDoesNotAllowToDeleteSystemServices() : void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_SYSTEM ])
        );

        $this->service_dao->shouldReceive('delete')->never();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::ERROR, M::any())->once();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['id' => '120']);
    }
}
