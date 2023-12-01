<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class UpdateControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HTTPRequest&MockObject $request;
    private PFUser $project_admin;
    private UpdateController $controller;
    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private Project $project;
    private BaseLayout&MockObject $layout;

    private UpdateCategoriesProcessor&MockObject $update_processor;

    /** @before */
    public function instantiateMocks(): void
    {
        $this->request               = $this->createMock(HTTPRequest::class);
        $this->layout                = $this->createMock(BaseLayout::class);
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);
        $this->update_processor      = $this->createMock(UpdateCategoriesProcessor::class);
        $this->project               = ProjectTestBuilder::aProject()->withId(42)->build();
        $this->project_admin         = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->build();

        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('42')
            ->willReturn($this->project);
        $this->administrator_checker->expects(self::once())->method('checkUserIsProjectAdministrator');

        $this->controller = new UpdateController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->update_processor,
        );
    }

    public function testItDisplaysAnErrorIfCategoriesIsNotAnArray(): void
    {
        $this->request->method('getCurrentUser')->willReturn($this->project_admin);
        $this->request->method('get')->with('categories')->willReturn('string');

        $this->layout->method('addFeedback')->with(Feedback::ERROR, self::anything());
        $exception_stop_exec_redirect = new \Exception("Redirect");
        $this->layout->method('redirect')->with('/project/42/admin/categories')->willThrowException($exception_stop_exec_redirect);

        self::expectExceptionObject($exception_stop_exec_redirect);
        $this->controller->process($this->request, $this->layout, ['project_id' => '42']);
    }
}
