<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Download;

use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanFileDownloadCORSTest extends TestCase
{
    public function testResponseIsGeneratedOnOptionsRequest(): void
    {
        $file_download_cors_middleware = new DocmanFileDownloadCORS(HTTPFactoryBuilder::responseFactory());

        $request = (new NullServerRequest())->withMethod('OPTIONS');

        $request_handler = $this->createMock(RequestHandlerInterface::class);
        $request_handler->expects($this->never())->method('handle');

        $response = $file_download_cors_middleware->process($request, $request_handler);
        self::assertEquals(['OPTIONS', 'GET'], $response->getHeader('Access-Control-Allow-Methods'));
    }

    public function testRequestIsGivenToTheNextRequestHandlerWhenItsNotAnOPTIONS(): void
    {
        $response_factory              = HTTPFactoryBuilder::responseFactory();
        $file_download_cors_middleware = new DocmanFileDownloadCORS($response_factory);

        $request = (new NullServerRequest())->withMethod('GET');

        $request_handler = $this->createMock(RequestHandlerInterface::class);
        $request_handler->expects($this->once())->method('handle')->willReturn($response_factory->createResponse());

        $response = $file_download_cors_middleware->process($request, $request_handler);
        self::assertEquals(['OPTIONS', 'GET'], $response->getHeader('Access-Control-Allow-Methods'));
    }
}
