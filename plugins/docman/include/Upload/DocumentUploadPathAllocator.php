<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

final class DocumentUploadPathAllocator
{
    /**
     * @return string
     */
    private function getBasePath()
    {
        return \ForgeConfig::get('tmp_dir') . '/docman/ongoing-upload/';
    }

    /**
     * @return string
     */
    public function getPathForItemBeingUploaded($item_id)
    {
        return $this->getBasePath() . $item_id;
    }

    /**
     * @return array<string,string>
     */
    public function getCurrentlyUsedAllocatedPathsPerExpectedItemIDs()
    {
        $base_path = $this->getBasePath();
        if (! is_dir($base_path)) {
            return [];
        }

        $paths = [];

        $directory_iterator = new \DirectoryIterator($base_path);
        foreach ($directory_iterator as $file_info) {
            if ($file_info->isFile()) {
                $paths[$file_info->getFilename()] = $file_info->getPathname();
            }
        }

        return $paths;
    }
}
