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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class ClientWrapperTest extends TestCase
{
    private ClientWrapper $wrapper;
    private RequestFactoryInterface&MockObject $factory;
    private ClientInterface&MockObject $client;

    protected function setUp(): void
    {
        $this->client  = $this->createMock(ClientInterface::class);
        $this->factory = $this->createMock(RequestFactoryInterface::class);
        $this->wrapper = new JiraCloudClient($this->client, $this->factory, 'https://example.com');
    }

    public function testItBuildsAnUrlFromLatestAPIAndReturnContent(): void
    {
        $this->factory->expects($this->once())->method('createRequest')->with('GET', 'https://example.com/rest/api/3/project');
        $this->client->expects($this->once())->method('sendRequest')->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse());
        $this->wrapper->getUrl('/rest/api/3/project');
    }

    public function testItThrowsAnExceptionIfStatusCodeIsNot200(): void
    {
        $this->factory->expects($this->once())->method('createRequest')->with('GET', 'https://example.com/rest/api/3/project')
            ->willReturn(HTTPFactoryBuilder::requestFactory()->createRequest('GET', 'https://example.com/rest/api/3/project'));
        $this->client->expects($this->once())->method('sendRequest')->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse(403));

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionCode(403);
        $this->wrapper->getUrl('/rest/api/3/project');
    }
}
