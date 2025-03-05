<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\MediawikiStandalone\Permissions\IBuildUserPermissionsStub;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RejectNonMediawikiAdministratorMiddlewareTest extends TestCase
{
    public function testProcessThrowsWhenProjectIsNotAnAttributeOfRequest(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request = new NullServerRequest();

        $middleware = new RejectNonMediawikiAdministratorMiddleware(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            IBuildUserPermissionsStub::buildWithFullAccess(),
        );

        $this->expectException(\LogicException::class);
        $middleware->process($request, $handler);
    }

    public function testProcessWhenMediawikiAdministrator(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $middleware = new RejectNonMediawikiAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            IBuildUserPermissionsStub::buildWithFullAccess(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );

        $captured_request = $handler->getCapturedRequest();
        if (! $captured_request) {
            self::fail('Failed to capture the request');
            return;
        }
        self::assertSame(
            $user,
            $captured_request->getAttribute(\PFUser::class)
        );
    }

    public function testProcessThrowsWhenUserIsOnlyWriter(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $middleware = new RejectNonMediawikiAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            IBuildUserPermissionsStub::buildWithWriter(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);

        $middleware->process($request, $handler);
    }

    public function testProcessThrowsWhenUserIsOnlyReader(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $middleware = new RejectNonMediawikiAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            IBuildUserPermissionsStub::buildWithReader(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);

        $middleware->process($request, $handler);
    }

    public function testProcessThrowsWhenUserHasNoAccess(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $middleware = new RejectNonMediawikiAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            IBuildUserPermissionsStub::buildWithNoAccess(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);

        $middleware->process($request, $handler);
    }
}
