<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Http\Client;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class FilteredOutboundHTTPResponseAlerterTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveInformationThatRequestHasBeenFiltered(): void
    {
        \ForgeConfig::set(OutboundHTTPRequestProxy::PROXY, '');

        $logger  = new TestLogger();
        $dao     = $this->createMock(FilteredOutboundHTTPResponseAlerterDAO::class);
        $alerter = new FilteredOutboundHTTPResponseAlerter($logger, $dao);

        $dao->expects(self::atLeastOnce())->method('markNewFilteredRequest');

        $alerter->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            fn () => new \Http\Promise\FulfilledPromise(
                HTTPFactoryBuilder::responseFactory()->createResponse(407)->withHeader('X-Smokescreen-Error', 'Some error')
            ),
            fn (\Http\Promise\Promise $promise) => $promise,
        );

        self::assertTrue($logger->hasErrorRecords());
    }

    public function testIgnoresFilteredRequestsWhenAdministratorsHaveDefinedAProxy(): void
    {
        \ForgeConfig::set(OutboundHTTPRequestProxy::PROXY, 'internal-proxy:8080');

        $logger  = new TestLogger();
        $alerter = new FilteredOutboundHTTPResponseAlerter($logger, $this->createStub(FilteredOutboundHTTPResponseAlerterDAO::class));

        $alerter->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            fn () => new \Http\Promise\FulfilledPromise(
                HTTPFactoryBuilder::responseFactory()->createResponse(407)->withHeader('X-Smokescreen-Error', 'Some error')
            ),
            fn (\Http\Promise\Promise $promise) => $promise,
        );

        self::assertFalse($logger->hasErrorRecords());
    }

    public function testIgnoresFilteredRequestsWhenAdministratorsHaveDisabledFiltering(): void
    {
        \ForgeConfig::set(OutboundHTTPRequestProxy::FILTERING_PROXY_USAGE, OutboundHTTPRequestProxy::FILTERING_PROXY_DISABLED);

        $logger  = new TestLogger();
        $alerter = new FilteredOutboundHTTPResponseAlerter($logger, $this->createStub(FilteredOutboundHTTPResponseAlerterDAO::class));

        $alerter->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            fn () => new \Http\Promise\FulfilledPromise(
                HTTPFactoryBuilder::responseFactory()->createResponse(407)->withHeader('X-Smokescreen-Error', 'Some error')
            ),
            fn (\Http\Promise\Promise $promise) => $promise,
        );

        self::assertFalse($logger->hasErrorRecords());
    }

    public function testDoesNotNothingWhenResponseIsNotA407(): void
    {
        $logger  = new TestLogger();
        $alerter = new FilteredOutboundHTTPResponseAlerter($logger, $this->createStub(FilteredOutboundHTTPResponseAlerterDAO::class));

        $alerter->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            fn () => new \Http\Promise\FulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse(200)),
            fn (\Http\Promise\Promise $promise) => $promise,
        );

        self::assertFalse($logger->hasErrorRecords());
    }
}
