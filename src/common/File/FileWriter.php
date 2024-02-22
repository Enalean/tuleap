<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

use Psl\File\WriteMode;
use function Psl\File\write;
use function Psl\Filesystem\change_permissions;
use function Psl\Filesystem\create_temporary_file;
use function Psl\Filesystem\delete_file;
use function Psl\Filesystem\exists;
use function Psl\Filesystem\get_directory;

final class FileWriter
{
    private function __construct()
    {
    }

    /**
     * Write file in way resistant to race conditions when the same file is written by simultaneous processes
     *
     * @psalm-param non-empty-string $file_path
     */
    public static function writeFile(string $file_path, string $content, int $chmod = 0644): void
    {
        $destination_directory = get_directory($file_path);
        $temporary_file        = create_temporary_file($destination_directory);

        try {
            write($temporary_file, $content, WriteMode::TRUNCATE);
            change_permissions($temporary_file, $chmod);
            if (! rename($temporary_file, $file_path)) {
                throw new \RuntimeException(sprintf('Could not move %s to %s', $temporary_file, $file_path));
            }
        } finally {
            if (exists($temporary_file)) {
                delete_file($temporary_file);
            }
        }
    }
}
