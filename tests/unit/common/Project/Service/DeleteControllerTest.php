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
use Project;
use Service;
use ServiceDao;
use ServiceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;

final class DeleteControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var DeleteController */
    private $controller;
    /** @var M\MockInterface|ServiceDao */
    private $service_dao;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever */
    private $project_retriever;
    /** @var CSRFSynchronizerToken|M\MockInterface */
    private $csrf_token;
    /** @var M\MockInterface|ServiceManager */
    private $service_manager;
    /** @var HTTPRequest|M\MockInterface */
    private $request;
    /** @var M\MockInterface|BaseLayout */
    private $layout;
    /** @var M\MockInterface|Project */
    private $project;
    /** @var int */
    private $project_id;
    private $service_id;
    /** * @var M\LegacyMockInterface|M\MockInterface|Project */
    private $default_template_project;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker */
    private $administrator_checker;

    protected function setUp(): void
    {
        $this->service_dao           = M::mock(ServiceDao::class);
        $project_admin               = M::mock(PFUser::class);
        $this->service_manager       = M::mock(ServiceManager::class);
        $this->project_retriever     = M::mock(ProjectRetriever::class);
        $this->administrator_checker = M::mock(ProjectAdministratorChecker::class);

        $this->service_id = '14';

        $this->project_id = 120;
        $this->project    = M::mock(Project::class, ['getID' => (string) $this->project_id]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with((string) $this->project_id)
            ->andReturn($this->project);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($project_admin, $this->project);

        $this->default_template_project = M::mock(Project::class, ['getID' => (string) Project::DEFAULT_TEMPLATE_PROJECT_ID]);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with((string) Project::DEFAULT_TEMPLATE_PROJECT_ID)
            ->andReturn($this->default_template_project);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($project_admin, $this->default_template_project);

        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->once()->byDefault();
        $this->request = M::mock(HTTPRequest::class);
        $this->request->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($project_admin);
        $this->layout = M::mock(BaseLayout::class);
        $this->request->shouldReceive('getValidated')->with('service_id', M::andAnyOtherArgs())->andReturn(
            $this->service_id
        )->byDefault();

        $this->controller = new DeleteController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->service_dao,
            $this->csrf_token,
            $this->service_manager
        );
    }

    public function testItDeletesOneService(): void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->shouldReceive('delete')->with($this->project_id, $this->service_id)->andReturnTrue();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::INFO, M::any())->once();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '120']);
    }

    public function testItDeletesAllServicesWhenItsInDefaultTemplate(): void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->default_template_project, ['service_id' => $this->service_id, 'short_name' => 'homepage', 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->shouldReceive('delete')->with((string) Project::DEFAULT_TEMPLATE_PROJECT_ID, $this->service_id)->andReturnTrue();
        $this->service_dao->shouldReceive('deleteFromAllProjects')->with('homepage')->once()->andReturnTrue();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::INFO, M::any())->twice();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => (string) Project::DEFAULT_TEMPLATE_PROJECT_ID]);
    }

    public function testItDoesNotAllowToDeleteSystemServices(): void
    {
        $this->service_manager->shouldReceive('getService')->with($this->service_id)->andReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_SYSTEM])
        );

        $this->service_dao->shouldReceive('delete')->never();

        $this->layout->shouldReceive('addFeedback')->with(Feedback::ERROR, M::any())->once();
        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '120']);
    }
}
