<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ClientWrapperTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ClientWrapper
     */
    private $wrapper;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RequestFactoryInterface
     */
    private $factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ClientInterface
     */
    private $client;

    protected function setUp(): void
    {
        $this->client  = \Mockery::mock(ClientInterface::class);
        $this->factory = \Mockery::mock(RequestFactoryInterface::class);
        $this->wrapper = new ClientWrapper($this->client, $this->factory, "https://example.com");
    }

    public function testItBuildsAnUrlFromLatestAPIAndReturnContent(): void
    {
        $response      = \Mockery::mock(ResponseInterface::class);
        $response_body = \Mockery::mock(StreamInterface::class);

        $response_body->shouldReceive('getContents')->andReturn("")->once();

        $response->shouldReceive('getBody')->andReturn($response_body)->once();
        $response->shouldReceive('getStatusCode')->andReturn(200)->once();
        $this->factory->shouldReceive('createRequest')
            ->withArgs(['GET', "https://example.com/rest/api/latest/project"])->once();
        $this->client->shouldReceive('sendRequest')->andReturn($response)->once();
        $this->wrapper->getUrl("project");
    }

    public function testItThrowsAnExceptionIfStatusCodeIsNot200(): void
    {
        $response = \Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(403)->twice();
        $response->shouldReceive('getReasonPhrase')->andReturn("Forbidden")->once();
        $this->factory->shouldReceive('createRequest')
            ->withArgs(['GET', "https://example.com/rest/api/latest/project"])->once();
        $this->client->shouldReceive('sendRequest')->andReturn($response)->once();

        $this->expectException(JiraConnectionException::class);
        $this->wrapper->getUrl("project");
    }
}
