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

use Docman_File;
use Docman_Item;
use Docman_ItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanFileDownloadControllerTest extends TestCase
{
    private Docman_ItemFactory&MockObject $item_factory;
    private DocmanFileDownloadResponseGenerator&MockObject $response_generator;

    protected function setUp(): void
    {
        $this->item_factory       = $this->createMock(Docman_ItemFactory::class);
        $this->response_generator = $this->createMock(DocmanFileDownloadResponseGenerator::class);
    }

    public function testDownloadFailsWhenTheFileCanNotBeFound(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
            new NullLogger()
        );

        $this->item_factory->method('getItemFromDb')->willReturn(null);

        $request = (new NullServerRequest())->withAttribute('file_id', '1');

        self::expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testOnlyAFileCanBeDownloaded(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
            new NullLogger()
        );

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Item());

        $request = (new NullServerRequest())->withAttribute('file_id', '1');

        self::expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testDownloadFailsWhenRequestedVersionCannotBeFound(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
            new NullLogger()
        );

        $docman_file = new Docman_File(['item_id' => '1']);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $request = (new NullServerRequest())
            ->withAttribute('file_id', '1')
            ->withAttribute('version_id', '1');

        $this->response_generator->method('generateResponse')->willThrowException(new VersionNotFoundException($docman_file, 1));

        self::expectException(NotFoundException::class);
        self::expectExceptionMessageMatches('/version/');
        $controller->handle($request);
    }

    public function testDownloadFailsWhenNoCurrentUserCanBeFoundWithTheRequest(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(null),
            new NullLogger()
        );

        $docman_file = new Docman_File(['item_id' => '1']);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $request = (new NullServerRequest())
            ->withAttribute('file_id', '1')
            ->withAttribute('version_id', '1');

        self::expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testDownloadFailsWhenResponseCannotBeGenerated(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
            new NullLogger()
        );

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_File());

        $request = (new NullServerRequest())
            ->withAttribute('file_id', '1')
            ->withAttribute('version_id', null);

        $this->response_generator->method('generateResponse')->willThrowException($this->createMock(FileDownloadException::class));

        self::expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testFileItemCanBeDownloaded(): void
    {
        $controller = new DocmanFileDownloadController(
            new NoopSapiEmitter(),
            $this->item_factory,
            $this->response_generator,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
            new NullLogger()
        );

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_File());

        $request = (new NullServerRequest())
            ->withAttribute('file_id', '1')
            ->withAttribute('version_id', null);

        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $this->response_generator->method('generateResponse')->willReturn($expected_response);

        self::assertSame($expected_response, $controller->handle($request));
    }
}
