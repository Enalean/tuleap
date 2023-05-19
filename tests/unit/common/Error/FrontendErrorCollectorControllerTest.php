<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Error;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\ProvideCurrentUser;

final class FrontendErrorCollectorControllerTest extends TestCase
{
    private const CURRENT_USER_ID = 102;

    private \Psr\Http\Message\StreamFactoryInterface $stream_factory;
    private TestLogger $logger;
    private Prometheus $prometheus;
    private FrontendErrorCollectorController $controller;

    protected function setUp(): void
    {
        $this->stream_factory = HTTPFactoryBuilder::streamFactory();
        $this->logger         = new TestLogger();
        $this->prometheus     = Prometheus::getInMemory();
        $this->controller     = new FrontendErrorCollectorController(
            HTTPFactoryBuilder::responseFactory(),
            $this->logger,
            $this->prometheus,
            new class (self::CURRENT_USER_ID) implements ProvideCurrentUser
            {
                public function __construct(private $user_id)
                {
                }

                public function getCurrentUser(): \PFUser
                {
                    return UserTestBuilder::buildWithId($this->user_id);
                }
            },
            new SapiEmitter()
        );
    }

    public function testLogsCollectedFrontendErrors(): void
    {
        $expected_user_agent   = 'My User Agent';
        $expected_error_string = "Some error...";
        $request               = (new NullServerRequest())
            ->withHeader('User-Agent', $expected_user_agent)
            ->withBody(
                $this->stream_factory->createStream(
                    "{\"error\":\"$expected_error_string\"}"
                )
            );

        $response = $this->controller->handle($request);

        self::assertEquals(204, $response->getStatusCode());
        self::assertTrue($this->logger->hasErrorThatContains('My User Agent'));
        self::assertTrue($this->logger->hasErrorThatContains((string) self::CURRENT_USER_ID));
        self::assertTrue($this->logger->hasErrorThatContains($expected_error_string));
        self::assertStringContainsString('collected_frontend_errors_total 1', $this->prometheus->renderText());
    }

    public function testRejectsInvalidErrorReport(): void
    {
        $request = (new NullServerRequest())->withBody($this->stream_factory->createStream('{broken_body'));

        $response = $this->controller->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testRejectsIncorrectlyFormattedErrorReport(): void
    {
        $request = (new NullServerRequest())->withBody($this->stream_factory->createStream('{}'));

        $response = $this->controller->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($this->logger->hasErrorRecords());
    }
}
