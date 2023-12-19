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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Service;
use ServiceDao;
use ServiceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DeleteControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private DeleteController $controller;
    private ServiceDao&MockObject $service_dao;
    private ProjectRetriever&MockObject $project_retriever;
    private CSRFSynchronizerToken&MockObject $csrf_token;
    private ServiceManager&MockObject $service_manager;
    private HTTPRequest&MockObject $request;
    private BaseLayout&MockObject $layout;
    private Project $project;
    private int $project_id;
    private string $service_id;
    private Project $default_template_project;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private \PFUser $project_admin;

    protected function setUp(): void
    {
        $this->project_admin         = UserTestBuilder::buildWithDefaults();
        $this->service_dao           = $this->createMock(ServiceDao::class);
        $this->service_manager       = $this->createMock(ServiceManager::class);
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);

        $this->service_id = '14';

        $this->project_id = 120;
        $this->project    = ProjectTestBuilder::aProject()->withId($this->project_id)->build();

        $this->default_template_project = ProjectTestBuilder::aProject()->withId(Project::DEFAULT_TEMPLATE_PROJECT_ID)->build();

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);
        $this->csrf_token->expects(self::once())->method('check');
        $this->request = $this->createMock(HTTPRequest::class);
        $this->request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->project_admin);
        $this->layout = $this->createMock(BaseLayout::class);
        $this->request->method('getValidated')->with('service_id', self::anything())->willReturn($this->service_id);

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
        $this->service_manager->method('getService')->with($this->service_id)->willReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->method('delete')->with($this->project_id, $this->service_id)->willReturn(true);
        $this->project_retriever->method('getProjectFromId')
            ->with((string) $this->project_id)
            ->willReturn($this->project);
        $this->administrator_checker->method('checkUserIsProjectAdministrator')
            ->with($this->project_admin, $this->project);

        $this->layout->expects(self::once())->method('addFeedback')->with(Feedback::INFO, self::anything());
        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '120']);
    }

    public function testItDeletesAllServicesWhenItsInDefaultTemplate(): void
    {
        $this->service_manager->method('getService')->with($this->service_id)->willReturn(
            new Service($this->default_template_project, ['service_id' => $this->service_id, 'short_name' => 'homepage', 'scope' => Service::SCOPE_PROJECT])
        );

        $this->service_dao->method('delete')->with((string) Project::DEFAULT_TEMPLATE_PROJECT_ID, $this->service_id)->willReturn(true);
        $this->service_dao->expects(self::once())->method('deleteFromAllProjects')->with('homepage')->willReturn(true);
        $this->project_retriever->method('getProjectFromId')
            ->with((string) Project::DEFAULT_TEMPLATE_PROJECT_ID)
            ->willReturn($this->default_template_project);
        $this->administrator_checker->method('checkUserIsProjectAdministrator')
            ->with($this->project_admin, $this->default_template_project);

        $this->layout->expects(self::exactly(2))->method('addFeedback')->with(Feedback::INFO, self::anything());
        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => (string) Project::DEFAULT_TEMPLATE_PROJECT_ID]);
    }

    public function testItDoesNotAllowToDeleteSystemServices(): void
    {
        $this->service_manager->method('getService')->with($this->service_id)->willReturn(
            new Service($this->project, ['service_id' => $this->service_id, 'scope' => Service::SCOPE_SYSTEM])
        );

        $this->service_dao->expects(self::never())->method('delete');
        $this->project_retriever->method('getProjectFromId')
            ->with((string) $this->project_id)
            ->willReturn($this->project);
        $this->administrator_checker->method('checkUserIsProjectAdministrator')
            ->with($this->project_admin, $this->project);

        $this->layout->expects(self::once())->method('addFeedback')->with(Feedback::ERROR, self::anything());
        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '120']);
    }
}
