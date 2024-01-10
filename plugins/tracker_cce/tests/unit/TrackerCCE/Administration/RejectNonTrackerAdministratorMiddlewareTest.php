<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Administration;

use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

final class RejectNonTrackerAdministratorMiddlewareTest extends TestCase
{
    public function testProcessAttachesUserToRequest(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $middleware = new RejectNonTrackerAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user)
        );

        $request = (new NullServerRequest())->withAttribute(\Tracker::class, $tracker);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $user,
            $handler->getCapturedRequest()?->getAttribute(\PFUser::class)
        );
    }

    public function testNotFoundWhenUserIsNotTrackerAdmin(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $middleware = new RejectNonTrackerAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user)
        );

        $request = (new NullServerRequest())->withAttribute(\Tracker::class, $tracker);

        $this->expectException(NotFoundException::class);
        $middleware->process($request, $handler);
    }

    public function testExceptionWhenTrackerIsNotProvided(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $response = $this->createMock(ResponseInterface::class);
        $handler  = CaptureRequestHandler::withResponse($response);

        $middleware = new RejectNonTrackerAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user)
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);
        $middleware->process($request, $handler);
    }
}
