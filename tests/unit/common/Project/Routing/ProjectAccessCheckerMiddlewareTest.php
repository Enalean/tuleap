<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

final class ProjectAccessCheckerMiddlewareTest extends TestCase
{
    public function testProcessAttachesUserToRequest(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $access_checker = CheckProjectAccessStub::withValidAccess();
        $middleware     = new ProjectAccessCheckerMiddleware(
            $access_checker,
            ProvideCurrentUserStub::buildWithUser($user),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(102)
            ->build();

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $user,
            $handler->getCapturedRequest()?->getAttribute(\PFUser::class)
        );
    }

    public function testProcessThrowsExceptionIfUserCannotAccessProject(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $access_checker = CheckProjectAccessStub::withRestrictedUserWithoutAccess();
        $middleware     = new ProjectAccessCheckerMiddleware(
            $access_checker,
            ProvideCurrentUserStub::buildWithUser($user),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(102)
            ->build();

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);
        $middleware->process($request, $handler);
    }
}
