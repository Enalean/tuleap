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
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Document\Tests\Stubs\RecentlyVisited\RecordVisitStub;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanFileDownloadResponseGeneratorTest extends TestCase
{
    private const TEST_PROJECT_ID = 101;

    private BinaryFileResponseBuilder $binary_file_response_factory;
    private Docman_VersionFactory&MockObject $version_factory;
    private PFUser $current_user;
    private Docman_File $docman_file;
    private Docman_PermissionsManager&MockObject $permissions_manager;

    protected function setUp(): void
    {
        $this->binary_file_response_factory = new BinaryFileResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
        $this->version_factory              = $this->createMock(Docman_VersionFactory::class);
        $this->current_user                 = UserTestBuilder::buildWithId(456);
        $this->docman_file                  = new Docman_File(['group_id' => self::TEST_PROJECT_ID]);
        $this->permissions_manager          = $this->createMock(Docman_PermissionsManager::class);
        Docman_PermissionsManager::setInstance(self::TEST_PROJECT_ID, $this->permissions_manager);
    }

    protected function tearDown(): void
    {
        Docman_PermissionsManager::clearInstances();
    }

    public function testUserCanNotDownloadTheFileWithoutTheNeededPermissions(): void
    {
        $visit = RecordVisitStub::build();

        $response_generator = new DocmanFileDownloadResponseGenerator(
            $this->version_factory,
            $this->binary_file_response_factory,
            $visit,
        );

        $this->docman_file->setId(1);
        $this->permissions_manager->method('userCanAccess')->willReturn(false);

        $this->expectException(UserCannotAccessFileException::class);
        $response_generator->generateResponse(
            new NullServerRequest(),
            $this->current_user,
            $this->docman_file,
            null
        );

        self::assertFalse($visit->isSaved());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([null])]
    #[\PHPUnit\Framework\Attributes\TestWith([1])]
    public function testFileCanNotBeDownloadedIfTheVersionCannotBeFound(?int $version_id): void
    {
        $visit = RecordVisitStub::build();

        $response_generator = new DocmanFileDownloadResponseGenerator(
            $this->version_factory,
            $this->binary_file_response_factory,
            $visit,
        );

        $this->docman_file->setId(1);
        $this->permissions_manager->method('userCanAccess')->willReturn(true);

        $this->docman_file->setCurrentVersion(null);
        $this->version_factory->method('getSpecificVersion')->willReturn(null);

        $this->expectException(VersionNotFoundException::class);
        $response_generator->generateResponse(
            new NullServerRequest(),
            $this->current_user,
            $this->docman_file,
            $version_id
        );

        self::assertFalse($visit->isSaved());
    }

    public function testFileCanNotBeDownloadedIfItIsNotPresentOnTheFilesystem(): void
    {
        $visit = RecordVisitStub::build();

        $response_generator = new DocmanFileDownloadResponseGenerator(
            $this->version_factory,
            $this->binary_file_response_factory,
            $visit,
        );

        $this->docman_file->setId(1);
        $this->permissions_manager->method('userCanAccess')->willReturn(true);

        $directory = vfsStream::setup()->url();
        $version   = new Docman_Version(['id' => 1, 'item_id' => 1, 'path' => $directory . '/mydoc']);
        $this->docman_file->setCurrentVersion($version);

        $this->expectException(FileDoesNotExistException::class);
        $response_generator->generateResponse(
            new NullServerRequest(),
            $this->current_user,
            $this->docman_file,
            null
        );

        self::assertFalse($visit->isSaved());
    }

    public function testFileResponseCanBeGenerated(): void
    {
        $visit = RecordVisitStub::build();

        $response_generator = new DocmanFileDownloadResponseGenerator(
            $this->version_factory,
            $this->binary_file_response_factory,
            $visit,
        );

        $this->docman_file->setId(1);
        $this->permissions_manager->method('userCanAccess')->willReturn(true);

        $directory = vfsStream::setup()->url();
        $path      = $directory . '/mydoc';
        $version   = $this->createMock(Docman_Version::class);
        $version->method('getId')->willReturn(1);
        $version->method('getItemId')->willReturn(1);
        $version->method('getPath')->willReturn($path);
        $version->method('getFilename')->willReturn('mydoc');
        $version->method('getFiletype')->willReturn('application/octet-stream');
        $this->docman_file->setCurrentVersion($version);
        touch($path);

        $version->expects($this->once())->method('preDownload');

        $response_generator->generateResponse(
            new NullServerRequest(),
            $this->current_user,
            $this->docman_file,
            null
        );

        self::assertTrue($visit->isSaved());
    }
}
