<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\File;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DirectoryRemoverTest extends TestCase
{
    use TemporaryTestDirectory;

    public function testRemovesDirectory(): void
    {
        $base_path = vfsStream::setup()->url();

        \Psl\Filesystem\create_directory($base_path . '/d1');
        \Psl\Filesystem\create_directory($base_path . '/d1/d1.1');
        FileWriter::writeFile($base_path . '/d1/f1', 'content');
        FileWriter::writeFile($base_path . '/d1/d1.1/f1.1', 'content');


        DirectoryRemover::deleteDirectory($base_path . '/d1');

        self::assertDirectoryDoesNotExist($base_path . '/d1');
    }

    public function testRemovesDirectoryContainingABrokenSymlink(): void
    {
        $base_path = $this->getTmpDir();

        \Psl\Filesystem\create_directory($base_path . '/d1');
        FileWriter::writeFile($base_path . '/d1/f1', 'content');
        \Psl\Filesystem\create_symbolic_link($base_path . '/d1/f1', $base_path . '/d1/broken_symlink');
        \Psl\Filesystem\delete_file($base_path . '/d1/f1');

        DirectoryRemover::deleteDirectory($base_path . '/d1');

        self::assertDirectoryDoesNotExist($base_path . '/d1');
    }

    public function testOnlyRemovesSymlinkWhenTopDirectoryIsASymlink(): void
    {
        $base_path = $this->getTmpDir();

        \Psl\Filesystem\create_directory($base_path . '/d1');
        \Psl\Filesystem\create_symbolic_link($base_path . '/d1', $base_path . '/directory_to_remove');

        DirectoryRemover::deleteDirectory($base_path . '/directory_to_remove');

        self::assertFileDoesNotExist($base_path . '/directory_to_remove');
        self::assertDirectoryExists($base_path . '/d1');
    }
}
