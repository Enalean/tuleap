<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Layout\Logo;

class FileContentComparator
{
    public function doesFilesHaveTheSameContent(string $source_path, string $target_path): bool
    {
        if (! is_file($source_path) || ! is_readable($source_path)) {
            throw new \RuntimeException("$source_path does not exist or is not readable");
        }

        if (! is_file($target_path) || ! is_readable($target_path)) {
            throw new \RuntimeException("$target_path does not exist or is not readable");
        }

        if (! $this->areFilesTheSameSize($source_path, $target_path)) {
            return false;
        }

        return $this->areContentsTheSame($source_path, $target_path);
    }

    private function areFilesTheSameSize(string $source_path, string $target_path): bool
    {
        return filesize($source_path) === filesize($target_path);
    }

    private function areContentsTheSame(string $source_path, string $target_path): bool
    {
        $source_handle = fopen($source_path, 'rb');
        $target_handle = fopen($target_path, 'rb');

        $are_same = true;
        while (! feof($source_handle)) {
            if (fread($source_handle, 8192) !== fread($target_handle, 8192)) {
                $are_same = false;
                break;
            }
        }

        fclose($source_handle);
        fclose($target_handle);

        return $are_same;
    }
}
