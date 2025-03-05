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
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Upload\Section\File\DeleteExpiredFilesStub;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchExpiredUploadsStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileUploadCleanerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const ARTIDOC_ID = 123;

    public function testDanglingFilesBeingUploadedAreCleaned(): void
    {
        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('sys_data_dir', $tmp_dir->url());

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));

        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());

        $expired_item_id          = $identifier_factory->buildIdentifier();
        $expired_file_information = new FileBeingUploadedInformation($expired_item_id, 'Expired', 10, 0);
        $expired_item_path        = $path_allocator->getPathForItemBeingUploaded($expired_file_information);
        $this->createFileOnSystem($expired_item_path);

        $non_expired_item_id          = $identifier_factory->buildIdentifier();
        $non_expired_file_information = new FileBeingUploadedInformation($non_expired_item_id, 'Not expired', 10, 0);
        $non_expired_item_path        = $path_allocator->getPathForItemBeingUploaded($non_expired_file_information);
        $this->createFileOnSystem($non_expired_item_path);

        $deletor = DeleteExpiredFilesStub::build();
        $search  = SearchExpiredUploadsStub::withResults([new ExpiredFileInformation(
            self::ARTIDOC_ID,
            $expired_file_information->getID(),
            $expired_file_information->getName(),
            $expired_file_information->getLength(),
        ),
        ]);

        $cleaner = new FileUploadCleaner($search, $deletor, new DBTransactionExecutorPassthrough());

        self::assertFileExists($expired_item_path);
        self::assertFileExists($non_expired_item_path);

        $current_time = new \DateTimeImmutable();
        $cleaner->deleteDanglingFilesToUpload($current_time);

        self::assertTrue($deletor->isCalled());
        self::assertFileDoesNotExist($expired_item_path);
        self::assertFileExists($non_expired_item_path);
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
