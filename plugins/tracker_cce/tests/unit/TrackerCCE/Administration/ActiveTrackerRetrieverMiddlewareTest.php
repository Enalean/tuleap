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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

final class ActiveTrackerRetrieverMiddlewareTest extends TestCase
{
    public function testProcessAttachesActiveTrackerToRequest(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(101)->build();
        $retriever = RetrieveTrackerStub::withTracker($tracker);

        $middleware = new ActiveTrackerRetrieverMiddleware($retriever);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with(
            self::callback(
                fn (ServerRequestInterface $enriched_request): bool =>
                    $enriched_request->getAttribute(\Tracker::class) === $tracker
            )
        );

        $request = (new NullServerRequest())->withAttribute('id', '101');

        $middleware->process($request, $handler);
    }

    public function testNotFoundWhenNoId(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(101)->build();
        $retriever = RetrieveTrackerStub::withTracker($tracker);

        $middleware = new ActiveTrackerRetrieverMiddleware($retriever);

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = new NullServerRequest();

        $this->expectException(NotFoundException::class);

        $middleware->process($request, $handler);
    }

    public function testNotFoundWhenTrackerIsDeleted(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(101)->withDeletionDate(123)->build();
        $retriever = RetrieveTrackerStub::withTracker($tracker);

        $middleware = new ActiveTrackerRetrieverMiddleware($retriever);

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())->withAttribute('id', '101');

        $this->expectException(NotFoundException::class);

        $middleware->process($request, $handler);
    }
}
