<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use org\bovigo\vfs\vfsStream;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\Filesystem\create_directory_for_file;

final class UploadedArchiveForProjectControllerTest extends TestCase
{
    private const PROJECT_ID = 101;

    public function testNotCreatedFromArchive(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute(\Project::class, ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        $controller = new UploadedArchiveForProjectController(
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            RetrieveUploadedArchiveForProjectStub::withoutArchive(),
            new NoopSapiEmitter(),
        );

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testReturnedArchivePathDoesNotActullyExist(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute(\Project::class, ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        $path = '/path/to/archive.zip';

        $controller = new UploadedArchiveForProjectController(
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            RetrieveUploadedArchiveForProjectStub::withArchive($path),
            new NoopSapiEmitter(),
        );

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testHappyPath(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute(\Project::class, ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        $path = vfsStream::setup()->url() . '/path/to/archive.zip';
        create_directory_for_file($path);
        file_put_contents($path, 'dummy content');


        $controller = new UploadedArchiveForProjectController(
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            RetrieveUploadedArchiveForProjectStub::withArchive($path),
            new NoopSapiEmitter(),
        );

        $response = $controller->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('dummy content', $response->getBody()->getContents());
    }
}
