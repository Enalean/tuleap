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

declare(strict_types=1);

namespace Tuleap\APIExplorer\Specification\Swagger;

use ForgeConfig;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Luracast\Restler\Restler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\REST\ResourcesInjector;
use Tuleap\REST\RestlerFactory;

final class SwaggerJsonControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testBuildsSwaggerSpecificationGoldenTest(): void
    {
        ForgeConfig::set('codendi_cache_dir', vfsStream::setup()->url());

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldReceive('processEvent');

        $resources_injector = new class extends ResourcesInjector
        {
            public function populate(Restler $restler): void
            {
                $restler->addAPIClass(SwaggerJsonDemoResource::class, 'somepath');
            }
        };

        $controller = new SwaggerJsonController(
            new RestlerFactory(
                new \RestlerCache(),
                $resources_injector,
                $event_manager
            ),
            '11.13-2',
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            \Mockery::mock(EmitterInterface::class)
        );

        $response = $controller->handle(
            (new NullServerRequest())->withUri(HTTPFactoryBuilder::URIFactory()->createUri('https://example.com:8443/api/explorer/'))
        );

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/_fixtures/swagger.json', $response->getBody()->getContents());
    }
}
