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

namespace Tuleap\FRS;

use FRSFile;
use FRSFileFactory;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessException;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use URLVerification;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class FRSFileDownloadControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|URLVerification
     */
    private $url_verification;
    /**
     * @var FRSFileFactory|Mockery\MockInterface
     */
    private $file_factory;

    protected function setUp(): void
    {
        $this->url_verification = Mockery::mock(URLVerification::class);
        $this->file_factory     = Mockery::mock(FRSFileFactory::class);
    }

    public function testFileCanBeDownloaded(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(102);
        $server_request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn($current_user);
        $server_request->shouldReceive('getHeaderLine')->with('Range')->andReturn('');

        $frs_file = Mockery::mock(FRSFile::class);
        $this->file_factory->shouldReceive('getFRSFileFromDb')->andReturn($frs_file);
        $frs_file->shouldReceive('getGroup')->andReturn(Mockery::mock(Project::class));
        $frs_file->shouldReceive('userCanDownload')->andReturn(true);
        $frs_file->shouldReceive('isActive')->andReturn(true);
        $frs_file->shouldReceive('fileExists')->andReturn(true);
        $filepath  = vfsStream::setup()->url() . '/file';
        $file_data = 'ABCDE';
        file_put_contents($filepath, $file_data);
        $frs_file->shouldReceive('getFileLocation')->andReturn($filepath);
        $frs_file->shouldReceive('getFileName')->andReturn('my_file');

        $this->url_verification->shouldReceive('userCanAccessProject');

        $frs_file->shouldReceive('LogDownload')->once();

        $response = $controller->handle($server_request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testNonExistingFileRequestIsRejected(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');

        $this->file_factory->shouldReceive('getFRSFileFromDb')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanNotAccessTheProject(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(102);
        $server_request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn($current_user);

        $frs_file = Mockery::mock(FRSFile::class);
        $this->file_factory->shouldReceive('getFRSFileFromDb')->andReturn($frs_file);
        $frs_file->shouldReceive('getGroup')->andReturn(Mockery::mock(Project::class));

        $this->url_verification->shouldReceive('userCanAccessProject')
            ->andThrow(Mockery::mock(Project_AccessException::class));

        $this->expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheFileIsNotActive(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(102);
        $server_request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn($current_user);

        $frs_file = Mockery::mock(FRSFile::class);
        $this->file_factory->shouldReceive('getFRSFileFromDb')->andReturn($frs_file);
        $frs_file->shouldReceive('getGroup')->andReturn(Mockery::mock(Project::class));
        $frs_file->shouldReceive('userCanDownload')->andReturn(true);
        $frs_file->shouldReceive('isActive')->andReturn(false);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheFileIsNotReadableByTheUser(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(102);
        $server_request->shouldReceive('getAttribute')->with(RESTCurrentUserMiddleware::class)
            ->andReturn($current_user);

        $frs_file = Mockery::mock(FRSFile::class);
        $this->file_factory->shouldReceive('getFRSFileFromDb')->andReturn($frs_file);
        $frs_file->shouldReceive('getGroup')->andReturn(Mockery::mock(Project::class));
        $frs_file->shouldReceive('userCanDownload')->andReturn(true);
        $frs_file->shouldReceive('isActive')->andReturn(true);
        $frs_file->shouldReceive('fileExists')->andReturn(false);
        $frs_file->shouldReceive('getFileID')->andReturn(12);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->expectException(FRSFileNotPresentInStorage::class);
        $controller->handle($server_request);
    }
}
