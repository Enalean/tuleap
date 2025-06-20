<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Logs;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Option\Option;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\TrackerFunctions\Stubs\Logs\RetrievePayloadsForChangesetStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PayloadDownloaderControllerTest extends TestCase
{
    public function testRetrievesPayloadsForTrackerAdmins(): void
    {
        $tracker = $this->createStub(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('userIsAdmin')->willReturn(true);

        $controller = $this->buildController(
            $tracker,
            RetrievePayloadsForChangesetStub::withPayloads(new FunctionLogPayloads(102, 'source_payload', Option::fromValue('generated_payload')))
        );

        $request = (new NullServerRequest())
            ->withAttribute('changeset_id', '1');

        $response = $controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals('application/zip', $response->getHeaderLine('Content-Type'));
        // Look for the zip magic numbers at the beginning of the output
        $this->expectOutputRegex('/^\x{50}\x{4b}\x{03}\x{04}.+/');
        $response->getBody()->getContents();
    }

    public function testRejectsWhenNonAdminTriesToAccessThePayload(): void
    {
        $tracker = $this->createStub(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('userIsAdmin')->willReturn(false);

        $controller = $this->buildController(
            $tracker,
            RetrievePayloadsForChangesetStub::withPayloads(new FunctionLogPayloads(102, 'source_payload', Option::fromValue('generated_payload')))
        );

        $request = (new NullServerRequest())
            ->withAttribute('changeset_id', '1');

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testRejectsWhenNoLogsExistForASpecificChangeset(): void
    {
        $controller = $this->buildController(
            TrackerTestBuilder::aTracker()->build(),
            RetrievePayloadsForChangesetStub::noPayload(),
        );

        $request = (new NullServerRequest())
            ->withAttribute('changeset_id', '404');

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    private function buildController(\Tuleap\Tracker\Tracker $tracker, RetrievePayloadsForChangeset $payload_retriever): PayloadDownloaderController
    {
        return new PayloadDownloaderController(
            new NoopSapiEmitter(),
            $payload_retriever,
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            RetrieveTrackerStub::withTracker($tracker),
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory())
        );
    }
}
