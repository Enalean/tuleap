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

namespace Tuleap\Baseline\Adapter\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Baseline\Stub\FullAccessAuthorizationsStub;
use Tuleap\Baseline\Stub\ReaderAuthorizationsStub;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

class RejectNonBaselineAdministratorMiddlewareTest extends TestCase
{
    public function testProcessThrowsWhenProjectIsNotAnAttributeOfRequest(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request = new NullServerRequest();

        $middleware = new RejectNonBaselineAdministratorMiddleware(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            new ProjectAdministratorChecker(),
            new FullAccessAuthorizationsStub(),
        );

        $this->expectException(\LogicException::class);
        $middleware->process($request, $handler);
    }

    public function testProcessWhenBaselineAdministrator(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $checker = $this->createMock(ProjectAdministratorChecker::class);
        $checker
            ->expects(self::never())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project);

        $middleware = new RejectNonBaselineAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            $checker,
            new FullAccessAuthorizationsStub(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $user,
            $handler->getCapturedRequest()?->getAttribute(\PFUser::class)
        );
    }

    public function testProcessWhenProjectAdministrator(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $checker = $this->createMock(ProjectAdministratorChecker::class);
        $checker
            ->expects(self::atLeastOnce())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project);

        $middleware = new RejectNonBaselineAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            $checker,
            new ReaderAuthorizationsStub(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $user,
            $handler->getCapturedRequest()?->getAttribute(\PFUser::class)
        );
    }

    public function testProcessWhenNorBaselineNorProjectAdministrator(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $checker = $this->createMock(ProjectAdministratorChecker::class);
        $checker
            ->expects(self::atLeastOnce())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project)
            ->willThrowException(new ForbiddenException());

        $middleware = new RejectNonBaselineAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            $checker,
            new ReaderAuthorizationsStub(),
        );

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);

        $middleware->process($request, $handler);
    }
}
