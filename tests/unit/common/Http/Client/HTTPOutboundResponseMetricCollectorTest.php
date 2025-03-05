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

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HTTPOutboundResponseMetricCollectorTest extends TestCase
{
    public function testCollectsHTTPStatusCodeOnFulfilledRequests(): void
    {
        $this->assertStringContainsString(
            'tuleap_outbound_http_requests_total{status="fulfilled",http_status_code="200"} 1',
            $this->buildPrometheusText(new \Http\Promise\FulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse(200)))
        );
    }

    public function testCollectsHTTPStatusCodeOnSSRFFilteredRequests(): void
    {
        $this->assertStringContainsString(
            'tuleap_outbound_http_requests_total{status="ssrf_filtered",http_status_code="407"} 1',
            $this->buildPrometheusText(new \Http\Promise\FulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse(407)->withHeader('X-Smokescreen-Error', 'Something bad')))
        );
    }

    public function testCollectsOnlyKnownHTTPStatusCode(): void
    {
        $this->assertStringContainsString(
            'tuleap_outbound_http_requests_total{status="fulfilled",http_status_code="invalid"} 1',
            $this->buildPrometheusText(new \Http\Promise\FulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse(199)))
        );
    }

    public function testCollectsFailedRequests(): void
    {
        $this->assertStringContainsString(
            'tuleap_outbound_http_requests_total{status="failure",http_status_code="invalid"} 1',
            $this->buildPrometheusText(new \Http\Promise\RejectedPromise(new \Exception()))
        );
    }

    private function buildPrometheusText(\Http\Promise\Promise $received_promise): string
    {
        $prometheus = Prometheus::getInMemory();
        $collector  = new HTTPOutboundResponseMetricCollector($prometheus);

        $collector->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            fn () => $received_promise,
            fn (\Http\Promise\Promise $promise) => $promise,
        );

        return $prometheus->renderText();
    }
}
