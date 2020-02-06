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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class DocmanFileDownloadControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|EmitterInterface
     */
    private $emitter;
    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var DocmanFileDownloadResponseGenerator|Mockery\MockInterface
     */
    private $response_generator;

    protected function setUp() : void
    {
        $this->emitter            = Mockery::mock(EmitterInterface::class);
        $this->item_factory       = Mockery::mock(Docman_ItemFactory::class);
        $this->response_generator = Mockery::mock(DocmanFileDownloadResponseGenerator::class);
    }

    public function testDownloadFailsWhenTheFileCanNotBeFound() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->emitter,
            $this->item_factory,
            $this->response_generator,
            new \Psr\Log\NullLogger()
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(null);

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('file_id')->andReturn('1');

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testOnlyAFileCanBeDownloaded() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->emitter,
            $this->item_factory,
            $this->response_generator,
            new \Psr\Log\NullLogger()
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(Mockery::mock(Docman_Item::class));

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('file_id')->andReturn('1');

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testDownloadFailsWhenRequestedVersionCannotBeFound() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->emitter,
            $this->item_factory,
            $this->response_generator,
            new \Psr\Log\NullLogger()
        );

        $docman_file = Mockery::mock(Docman_File::class);
        $docman_file->shouldReceive('getId')->andReturn('1');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_file);

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getAttribute')->with('file_id')->andReturn('1');
        $request->shouldReceive('getAttribute')->with('version_id')->andReturn('1');

        $this->response_generator->shouldReceive('generateResponse')->andThrow(new VersionNotFoundException($docman_file, 1));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/version/');
        $controller->handle($request);
    }

    public function testDownloadFailsWhenResponseCannotBeGenerated() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->emitter,
            $this->item_factory,
            $this->response_generator,
            new \Psr\Log\NullLogger()
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(Mockery::mock(Docman_File::class));

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getAttribute')->with('file_id')->andReturn('1');
        $request->shouldReceive('getAttribute')->with('version_id')->andReturn(null);

        $this->response_generator->shouldReceive('generateResponse')->andThrow(
            Mockery::mock(FileDownloadException::class)
        );

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testFileItemCanBeDownloaded() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->emitter,
            $this->item_factory,
            $this->response_generator,
            new \Psr\Log\NullLogger()
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(Mockery::mock(Docman_File::class));

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getAttribute')->with('file_id')->andReturn('1');
        $request->shouldReceive('getAttribute')->with('version_id')->andReturn(null);

        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $this->response_generator->shouldReceive('generateResponse')->andReturn($expected_response);

        $this->assertSame($expected_response, $controller->handle($request));
    }
}
