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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessException;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use URLVerification;

final class FRSFileDownloadControllerTest extends TestCase
{
    /**
     * @var MockObject&URLVerification
     */
    private $url_verification;
    /**
     * @var MockObject&FRSFileFactory
     */
    private $file_factory;

    protected function setUp(): void
    {
        $this->url_verification = $this->createMock(URLVerification::class);
        $this->file_factory     = $this->createMock(FRSFileFactory::class);
    }

    public function testFileCanBeDownloaded(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();

        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub($current_user),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12')->withHeader('Range', '');

        $frs_file = $this->createMock(FRSFile::class);
        $this->file_factory->method('getFRSFileFromDb')->willReturn($frs_file);
        $frs_file->method('getGroup')->willReturn($this->createMock(Project::class));
        $frs_file->method('userCanDownload')->willReturn(true);
        $frs_file->method('isActive')->willReturn(true);
        $frs_file->method('fileExists')->willReturn(true);
        $filepath  = vfsStream::setup()->url() . '/file';
        $file_data = 'ABCDE';
        file_put_contents($filepath, $file_data);
        $frs_file->method('getFileLocation')->willReturn($filepath);
        $frs_file->method('getFileName')->willReturn('my_file');

        $this->url_verification->method('userCanAccessProject');

        $frs_file->expects(self::once())->method('LogDownload');

        $response = $controller->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testNonExistingFileRequestIsRejected(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $this->file_factory->method('getFRSFileFromDb')->willReturn(null);

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanNotAccessTheProject(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $controller   = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub($current_user),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $frs_file = $this->createMock(FRSFile::class);
        $this->file_factory->method('getFRSFileFromDb')->willReturn($frs_file);
        $frs_file->method('getGroup')->willReturn($this->createMock(Project::class));

        $this->url_verification->method('userCanAccessProject')
            ->willThrowException($this->createMock(Project_AccessException::class));

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheFileIsNotActive(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $controller   = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub($current_user),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $frs_file = $this->createMock(FRSFile::class);
        $this->file_factory->method('getFRSFileFromDb')->willReturn($frs_file);
        $frs_file->method('getGroup')->willReturn($this->createMock(Project::class));
        $frs_file->method('userCanDownload')->willReturn(true);
        $frs_file->method('isActive')->willReturn(false);

        $this->url_verification->method('userCanAccessProject');

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheFileIsNotReadableByTheUser(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $controller   = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub($current_user),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $frs_file = $this->createMock(FRSFile::class);
        $this->file_factory->method('getFRSFileFromDb')->willReturn($frs_file);
        $frs_file->method('getGroup')->willReturn($this->createMock(Project::class));
        $frs_file->method('userCanDownload')->willReturn(true);
        $frs_file->method('isActive')->willReturn(true);
        $frs_file->method('fileExists')->willReturn(false);
        $frs_file->method('getFileID')->willReturn(12);

        $this->url_verification->method('userCanAccessProject');

        self::expectException(FRSFileNotPresentInStorage::class);
        $controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenCurrentUserIsFoundWithTheRequest(): void
    {
        $controller = new FRSFileDownloadController(
            $this->url_verification,
            $this->file_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
            new CurrentRequestUserProviderStub(null),
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $frs_file = $this->createStub(FRSFile::class);
        $this->file_factory->method('getFRSFileFromDb')->willReturn($frs_file);

        self::expectException(NotFoundException::class);
        $controller->handle($server_request);
    }
}
