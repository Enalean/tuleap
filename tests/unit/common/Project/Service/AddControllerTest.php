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

namespace Tuleap\Project\Service;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class AddControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddController $controller;
    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private ServicePOSTDataBuilder&MockObject $data_builder;

    protected function setUp(): void
    {
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);
        $this->data_builder          = $this->createMock(ServicePOSTDataBuilder::class);
        $csrf_token                  = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller            = new AddController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->createMock(ServiceCreator::class),
            $this->data_builder,
            $csrf_token
        );

        $csrf_token->method('check');
    }

    public function testItRedirectsWhenServiceDataIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('102')
            ->willReturn($project);

        $request      = $this->createMock(\HTTPRequest::class);
        $current_user = UserTestBuilder::buildWithDefaults();
        $request->method('getCurrentUser')->willReturn($current_user);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($current_user, $project);
        $response = $this->createMock(BaseLayout::class);
        $this->data_builder
            ->expects(self::once())
            ->method('buildFromRequest')
            ->with($request, $project, self::anything(), $response)
            ->willThrowException(new InvalidServicePOSTDataException());

        $response->expects(self::once())->method('addFeedback');
        $response->expects(self::once())->method('redirect');
        $this->controller->process($request, $response, ['project_id' => '102']);
    }
}
