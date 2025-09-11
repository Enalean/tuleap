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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchUploadStub;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocFileBeingUploadedLockerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const int ARTIDOC_ID = 123;

    private string $data_dir;

    #[\Override]
    protected function setUp(): void
    {
        $path = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        mkdir($path);
        $this->data_dir = $path;

        \ForgeConfig::set('sys_data_dir', $this->data_dir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $folders = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->data_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($folders as $folder) {
            if ($folder->isDir()) {
                rmdir($folder->getPathname());
            } else {
                unlink($folder->getPathname());
            }
        }
        rmdir($this->data_dir);
    }

    public function testALockCanOnlyBeAcquiredOnce(): void
    {
        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $locker = new ArtidocFileBeingUploadedLocker(
            SearchUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $file_information->getID(),
                    $file_information->getName(),
                    $file_information->getLength(),
                ),
            ),
        );

        self::assertTrue($locker->lock($file_information));
        self::assertFalse($locker->lock($file_information));
    }

    public function testALockCannotBeAcquiredIfFileCannotBeFound(): void
    {
        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $locker = new ArtidocFileBeingUploadedLocker(
            SearchUploadStub::withoutFile(),
        );

        self::assertFalse($locker->lock($file_information));
    }

    public function testALockCanBeAcquiredAgainAfterHavingBeenReleased(): void
    {
        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $locker = new ArtidocFileBeingUploadedLocker(
            SearchUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $file_information->getID(),
                    $file_information->getName(),
                    $file_information->getLength(),
                ),
            )
        );

        self::assertTrue($locker->lock($file_information));
        $locker->unlock($file_information);
        self::assertTrue($locker->lock($file_information));
    }
}
