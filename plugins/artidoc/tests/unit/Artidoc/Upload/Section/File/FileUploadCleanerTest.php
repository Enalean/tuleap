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

namespace Tuleap\Artidoc\Upload\Section\File;

use org\bovigo\vfs\vfsStream;
use Tuleap\Artidoc\Stubs\Upload\Section\File\DeleteUnusableFilesStub;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchFileOngoingUploadIdsStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

final class FileUploadCleanerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testDanglingFilesBeingUploadedAreCleaned(): void
    {
        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $tmp_dir->url());

        $path_allocator = new ArtidocUploadPathAllocator();

        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());

        $existing_item_id                  = $identifier_factory->buildIdentifier();
        $existing_file_information         = new FileBeingUploadedInformation($existing_item_id, 'Filename', 10, 0);
        $existing_item_being_uploaded_path = $path_allocator->getPathForItemBeingUploaded($existing_file_information);
        $this->createFileOnSystem($existing_item_being_uploaded_path);

        $nonexisting_item_id           = $identifier_factory->buildIdentifier();
        $non_existing_file_information = new FileBeingUploadedInformation($nonexisting_item_id, 'Filename', 10, 0);
        $non_existing_item_path        = $path_allocator->getPathForItemBeingUploaded($non_existing_file_information);
        $this->createFileOnSystem($non_existing_item_path);

        $deletor                = DeleteUnusableFilesStub::build();
        $search_ongoing_uploads = SearchFileOngoingUploadIdsStub::withResults([$existing_item_id]);

        $cleaner = new FileUploadCleaner($path_allocator, $search_ongoing_uploads, $deletor);

        $current_time = new \DateTimeImmutable();
        $cleaner->deleteDanglingFilesToUpload($current_time);

        self::assertTrue($deletor->isCalled());
        self::assertFileExists($existing_item_being_uploaded_path);
        self::assertFileDoesNotExist($non_existing_item_path);
    }

    private function createFileOnSystem(string $path): void
    {
        $folder = dirname($path);
        if (! is_dir($folder) && ! mkdir($folder, 0777, true) && ! is_dir($folder)) {
            throw new \Exception(sprintf('Directory "%s" was not created', $folder));
        }

        touch($path);
    }
}
