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
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\TrackerCCE\Stubs\Administration\TrackerCSRFTokenProviderStub;

final class CheckTrackerCSRFMiddlewareTest extends TestCase
{
    public function testProcess(): void
    {
        $csrf = CSRFSynchronizerTokenStub::buildSelf();

        $middleware = new CheckTrackerCSRFMiddleware(TrackerCSRFTokenProviderStub::withToken($csrf));

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, TrackerTestBuilder::aTracker()->build());

        $this->assertSame(
            $response,
            $middleware->process($request, $handler)
        );

        self::assertTrue($csrf->hasBeenChecked());
    }
}
