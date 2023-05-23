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

namespace Tuleap\ContentSecurityPolicy;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;

final class CSPViolationReportToControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var TestLogger
     */
    private $logger;
    /**
     * @var CSPViolationReportToController
     */
    private $controller;

    public function setUp(): void
    {
        $this->stream_factory = HTTPFactoryBuilder::streamFactory();
        $this->logger         = new TestLogger();
        $this->controller     = new CSPViolationReportToController(
            new SapiEmitter(),
            HTTPFactoryBuilder::responseFactory(),
            $this->logger,
        );
    }

    public function testLogsCSPViolationReport(): void
    {
        $expected_user_agent = 'My User Agent';
        $request             = (new NullServerRequest())
            ->withHeader('User-Agent', $expected_user_agent)
            ->withBody(
                $this->stream_factory->createStream(
                    "{\"csp-report\":{\"blocked-uri\":\"https://tuleap.example.com/picture.png\",\"document-uri\":\"https://tuleap.example.com/somepage\",\"original-policy\":\"default-src 'none'; report-uri https://tuleap.example.com/csp-violation\",\"referrer\":\"\",\"violated-directive\":\"default-src\"}}"
                )
            );

        $response = $this->controller->handle($request);

        self::assertEquals(204, $response->getStatusCode());
        self::assertTrue($this->logger->hasInfoThatContains('My User Agent'));
    }

    public function testRejectsIncorrectlyFormattedCSPViolationReport(): void
    {
        $request = (new NullServerRequest())->withBody($this->stream_factory->createStream('{}'));

        $response = $this->controller->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($this->logger->hasInfoRecords());
    }

    public function testRejectsInvalidCSPViolationReport(): void
    {
        $request = (new NullServerRequest())->withBody($this->stream_factory->createStream('{'));

        $response = $this->controller->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($this->logger->hasInfoRecords());
    }
}
