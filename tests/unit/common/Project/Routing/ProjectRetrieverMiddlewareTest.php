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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\ProjectRetriever;

final class ProjectRetrieverMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProcessAttachesProjectToRequest(): void
    {
        $project_retriever = M::mock(ProjectRetriever::class);
        $middleware        = new ProjectRetrieverMiddleware($project_retriever);

        $project = new \Project(['group_id' => 102]);
        $project_retriever->shouldReceive('getProjectFromId')
            ->with('102')
            ->once()
            ->andReturn($project);
        $handler = M::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->with(M::capture($enriched_request));

        $request = (new NullServerRequest())->withAttribute('project_id', '102');

        $middleware->process($request, $handler);
        $this->assertSame($project, $enriched_request->getAttribute(\Project::class));
    }
}
