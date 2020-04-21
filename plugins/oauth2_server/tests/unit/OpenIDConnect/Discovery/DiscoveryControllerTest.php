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

namespace Tuleap\OAuth2Server\OpenIDConnect\Discovery;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;

final class DiscoveryControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var DiscoveryController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ConfigurationResponseRepresentationBuilder
     */
    private $representation_builder;

    protected function setUp(): void
    {
        $this->representation_builder = M::mock(ConfigurationResponseRepresentationBuilder::class);

        \ForgeConfig::set('sys_https_host', 'tuleap.example.com');
        $this->controller = new DiscoveryController(
            $this->representation_builder,
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            M::mock(EmitterInterface::class)
        );
    }

    public function testReturnsAJSONObject(): void
    {
        $this->representation_builder->shouldReceive('build')
            ->once()
            ->andReturn(new ConfigurationResponseRepresentation(['en-US'], ['openid']));
        $response = $this->controller->handle(new NullServerRequest());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody()->getContents());
    }
}
