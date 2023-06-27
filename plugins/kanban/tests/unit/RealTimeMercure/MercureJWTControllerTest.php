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
namespace Tuleap\Kanban\RealTimeMercure;

use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\JWT\generators\MercureJWTGeneratorImpl;
use Tuleap\JWT\generators\NullMercureJWTGenerator;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

final class MercureJWTControllerTest extends TestCase
{
    use \Tuleap\GlobalLanguageMock;
    use ForgeConfigSandbox;

    private \Psr\Http\Message\ResponseFactoryInterface $response_factory;
    private \Psr\Http\Message\StreamFactoryInterface $stream_factory;
    private EmitterInterface $emitter;
    private TestLogger $test_logger;
    private MercureJWTController $mercure_jwt_controller;
    private AgileDashboard_KanbanFactory $agile_dashboard_kanban_factory;
    private ProvideCurrentUserStub $user_manager;

    protected function setup(): void
    {
        parent::setup();


        $this->user_manager                   = ProvideCurrentUserStub::buildCurrentUserByDefault();
        $this->response_factory               = HTTPFactoryBuilder::responseFactory();
        $this->stream_factory                 = HTTPFactoryBuilder::streamFactory();
        $jwt_configuration                    = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText(str_repeat('a', 32)));
        $mercure_jwt_generator                = new MercureJWTGeneratorImpl($jwt_configuration);
        $this->emitter                        = new NoopSapiEmitter();
        $this->agile_dashboard_kanban_factory = $this->createStub(\AgileDashboard_KanbanFactory::class);
        $this->test_logger                    = new TestLogger();
        $this->mercure_jwt_controller         =  new MercureJWTController(
            $this->agile_dashboard_kanban_factory,
            $this->test_logger,
            $this->response_factory,
            $this->stream_factory,
            $this->user_manager,
            $mercure_jwt_generator,
            $this->emitter,
        );
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);
    }

    public function testNoError(): void
    {
        $this->agile_dashboard_kanban_factory->method('getKanban')->willReturn($this->createMock(\AgileDashboard_Kanban::class));
        $request =  (new NullServerRequest())->withUri(
            HTTPFactoryBuilder::URIFactory()->createUri('/12')
        );

        $response = $this->mercure_jwt_controller->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNoGenerator(): void
    {
        $this->agile_dashboard_kanban_factory->method('getKanban')->willReturn($this->createMock(\AgileDashboard_Kanban::class));
        $request                    =  (new NullServerRequest())->withUri(
            HTTPFactoryBuilder::URIFactory()->createUri('/12')
        );
        $null_mercure_jwt_generator = new NullMercureJWTGenerator();
        $controller                 = new MercureJWTController(
            $this->agile_dashboard_kanban_factory,
            $this->test_logger,
            $this->response_factory,
            $this->stream_factory,
            $this->user_manager,
            $null_mercure_jwt_generator,
            $this->emitter,
        );
        $response                   = $controller->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($this->test_logger->hasInfo('Error while generating the token in Kanban JWT Request'));
    }

    public function testKanbanNotFoundException(): void
    {
        $this->agile_dashboard_kanban_factory->method('getKanban')->willThrowException(new AgileDashboard_KanbanNotFoundException());
        $request  =  (new NullServerRequest())->withUri(
            HTTPFactoryBuilder::URIFactory()->createUri('/12')
        );
        $response = $this->mercure_jwt_controller->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($this->test_logger->hasInfoThatContains('Kanban error in generating the token in Kanban JWT Request'));
    }

    public function testKanbanCannotAccessException(): void
    {
        $this->agile_dashboard_kanban_factory->method('getKanban')->willThrowException(new AgileDashboard_KanbanCannotAccessException());
        $request  =  (new NullServerRequest())->withUri(
            HTTPFactoryBuilder::URIFactory()->createUri('/12')
        );
        $response = $this->mercure_jwt_controller->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($this->test_logger->hasInfoThatContains('Kanban error in generating the token in Kanban JWT Request'));
    }
}
