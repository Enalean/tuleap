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

namespace Tuleap\Project\Routing;

use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectByNameRetrieverMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testProcessAttachesProjectToRequest(): void
    {
        $project_retriever = $this->createMock(ProjectRetriever::class);
        $middleware        = new ProjectByNameRetrieverMiddleware($project_retriever);

        $project = ProjectTestBuilder::aProject()
            ->withId(102)
            ->build();
        $project_retriever
            ->method('getProjectFromName')
            ->with('acme')
            ->willReturn($project);

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())->withAttribute('project_name', 'acme');

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $project,
            $handler->getCapturedRequest()->getAttribute(\Project::class)
        );
    }
}
