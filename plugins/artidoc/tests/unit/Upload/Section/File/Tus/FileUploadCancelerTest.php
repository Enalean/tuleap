<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use org\bovigo\vfs\vfsStream;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Upload\Section\File\DeleteFileUploadStub;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchUploadStub;
use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileUploadCancelerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const ARTIDOC_ID = 123;

    public function testDocumentBeingUploadedIsCleanedWhenTheUploadIsCancelled(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup()->url());

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $deletor = DeleteFileUploadStub::build();
        $search  = SearchUploadStub::withFile(
            new UploadFileInformation(
                self::ARTIDOC_ID,
                $file_information->getID(),
                $file_information->getName(),
                $file_information->getLength(),
            ),
        );

        $canceler = new FileUploadCanceler($search, $deletor);

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
        $item_path      = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($item_path), 0777, true);
        touch($path_allocator->getPathForItemBeingUploaded($file_information));

        $canceler->terminateUpload($file_information);

        self::assertTrue($deletor->isCalled());
        self::assertFileDoesNotExist($item_path);
    }

    public function testCancellingAnUploadThatHasNotYetStartedDoesNotGiveAWarning(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup()->url());

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $deletor = DeleteFileUploadStub::build();
        $search  = SearchUploadStub::withFile(
            new UploadFileInformation(
                self::ARTIDOC_ID,
                $file_information->getID(),
                $file_information->getName(),
                $file_information->getLength(),
            ),
        );

        $canceler = new FileUploadCanceler($search, $deletor);

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
        $item_path      = $path_allocator->getPathForItemBeingUploaded($file_information);

        $canceler->terminateUpload($file_information);

        self::assertTrue($deletor->isCalled());
        self::assertFileDoesNotExist($item_path);
    }

    public function testCancellingAnUploadThatDoesNotExistDeletesNothing(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup()->url());

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $deletor = DeleteFileUploadStub::build();
        $search  = SearchUploadStub::withoutFile();

        $canceler = new FileUploadCanceler($search, $deletor);

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
        $item_path      = $path_allocator->getPathForItemBeingUploaded($file_information);

        $canceler->terminateUpload($file_information);

        self::assertFalse($deletor->isCalled());
        self::assertFileDoesNotExist($item_path);
    }
}
