<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\ProFTPd\Directory;

class DirectoryPathParser
{

    public const BASE_PATH = '';

    /**
     * @param string $path
     * @return DirectoryPathCollection
     */
    public function getPathParts($path)
    {
        $parts = new DirectoryPathCollection();

        $path_to_part = '';
        foreach (array_filter(explode('/', $path)) as $part_name) {
            if ($path_to_part) {
                $path_to_part .= '/';
            }

            $path_to_part .= $part_name;
            $parts->add(new DirectoryPathPart($part_name, $path_to_part));
        }

        return $parts;
    }

    /**
     * @param string $path_from_request
     * @return string
     */
    public function getCleanPath($path_from_request)
    {
        if (! $path_from_request) {
            return self::BASE_PATH;
        }

        $path = urldecode($path_from_request);

        if (! $this->shouldGoToParentDirectory($path)) {
            return $path;
        }

        $safe_path = $this->getSafeParentDirectoryPath($path);

        return $this->getParentDirectory($safe_path);
    }

    private function shouldGoToParentDirectory($path)
    {
        return strstr($path, '..');
    }

    private function getSafeParentDirectoryPath($path)
    {
        return strstr($path, '..', true);
    }

    private function getParentDirectory($safe_path)
    {
        $clean_path = rtrim($safe_path, '/');

        $path_last_slash_position = strrpos($clean_path, '/');
        if ($path_last_slash_position) {
            return substr($safe_path, 0, $path_last_slash_position);
        } else {
            return self::BASE_PATH;
        }
    }
}
