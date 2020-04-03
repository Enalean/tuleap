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
use Docman_PermissionsManager;
use Docman_Version;
use Docman_VersionFactory;
use Mockery;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;

final class DocmanFileDownloadResponseGeneratorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private const TEST_PROJECT_ID = 101;

    /**
     * @var BinaryFileResponseBuilder
     */
    private $binary_file_response_factory;
    /**
     * @var Docman_VersionFactory|MockInterface
     */
    private $version_factory;
    /**
     * @var PFUser|MockInterface
     */
    private $current_user;
    /**
     * @var Docman_File|MockInterface
     */
    private $docman_file;
    /**
     * @var Docman_PermissionsManager|MockInterface
     */
    private $permissions_manager;

    protected function setUp(): void
    {
        $this->binary_file_response_factory = new BinaryFileResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
        $this->version_factory              = Mockery::mock(Docman_VersionFactory::class);
        $this->current_user                 = Mockery::mock(PFUser::class);
        $this->docman_file                  = Mockery::mock(Docman_File::class);
        $this->docman_file->shouldReceive('getGroupId')->andReturn(self::TEST_PROJECT_ID);
        $this->permissions_manager = Mockery::mock(Docman_PermissionsManager::class);
        Docman_PermissionsManager::setInstance(self::TEST_PROJECT_ID, $this->permissions_manager);
    }

    protected function tearDown(): void
    {
        Docman_PermissionsManager::clearInstances();
    }

    public function testUserCanNotDownloadTheFileWithoutTheNeededPermissions(): void
    {
        $response_generator = new DocmanFileDownloadResponseGenerator($this->version_factory, $this->binary_file_response_factory);

        $this->docman_file->shouldReceive('getId')->andReturn(1);
        $this->current_user->shouldReceive('getId')->andReturn(456);
        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(false);

        $this->expectException(UserCannotAccessFileException::class);
        $response_generator->generateResponse(
            Mockery::mock(ServerRequestInterface::class),
            $this->current_user,
            $this->docman_file,
            null
        );
    }

    /**
     * @testWith [null]
     *           [1]
     */
    public function testFileCanNotBeDownloadedIfTheVersionCannotBeFound(?int $version_id): void
    {
        $response_generator = new DocmanFileDownloadResponseGenerator($this->version_factory, $this->binary_file_response_factory);

        $this->docman_file->shouldReceive('getId')->andReturn(1);
        $this->current_user->shouldReceive('getId')->andReturn(456);
        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);

        $this->docman_file->shouldReceive('getCurrentVersion')->andReturn(null);
        $this->version_factory->shouldReceive('getSpecificVersion')->andReturn(null);

        $this->expectException(VersionNotFoundException::class);
        $response_generator->generateResponse(
            Mockery::mock(ServerRequestInterface::class),
            $this->current_user,
            $this->docman_file,
            $version_id
        );
    }

    public function testFileCanNotBeDownloadedIfItIsNotPresentOnTheFilesystem(): void
    {
        $response_generator = new DocmanFileDownloadResponseGenerator($this->version_factory, $this->binary_file_response_factory);

        $this->docman_file->shouldReceive('getId')->andReturn(1);
        $this->current_user->shouldReceive('getId')->andReturn(456);
        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);

        $version = Mockery::mock(Docman_Version::class);
        $version->shouldReceive('getId')->andReturn(1);
        $version->shouldReceive('getItemId')->andReturn(1);
        $this->docman_file->shouldReceive('getCurrentVersion')->andReturn($version);

        $directory = vfsStream::setup()->url();
        $version->shouldReceive('getPath')->andReturn($directory . '/mydoc');

        $this->expectException(FileDoesNotExistException::class);
        $response_generator->generateResponse(
            Mockery::mock(ServerRequestInterface::class),
            $this->current_user,
            $this->docman_file,
            null
        );
    }

    public function testFileResponseCanBeGenerated(): void
    {
        $response_generator = new DocmanFileDownloadResponseGenerator($this->version_factory, $this->binary_file_response_factory);

        $this->docman_file->shouldReceive('getId')->andReturn(1);
        $this->current_user->shouldReceive('getId')->andReturn(456);
        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);

        $version = Mockery::mock(Docman_Version::class);
        $version->shouldReceive('getId')->andReturn(1);
        $version->shouldReceive('getItemId')->andReturn(1);
        $this->docman_file->shouldReceive('getCurrentVersion')->andReturn($version);

        $directory = vfsStream::setup()->url();
        $path      = $directory . '/mydoc';
        touch($path);
        $version->shouldReceive('getPath')->andReturn($path);
        $version->shouldReceive('getFilename')->andReturn('mydoc');
        $version->shouldReceive('getFiletype')->andReturn('application/octet-stream');

        $version->shouldReceive('preDownload')->once();

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->andReturn('');

        $response_generator->generateResponse(
            $request,
            $this->current_user,
            $this->docman_file,
            null
        );
    }
}
