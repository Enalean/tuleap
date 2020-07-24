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

use DirectoryIterator;
use UnexpectedValueException;

class DirectoryParser
{

    private $base_dir;

    public function __construct($base_dir)
    {
        $this->base_dir = $base_dir;
    }

    public function isFile($path)
    {
        return is_file($this->getFullPath($path));
    }

    public function getFullPath($path)
    {
        return $this->base_dir . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Parse the content of a given directory
     *
     * @param  String $path The path to parse
     *
     * @return DirectoryItemCollection
     */
    public function parseDirectory($path, $remove_parent_directory_listing)
    {
        $items = [
            'folders' => [],
            'files'   => [],
        ];
        $directory_iterator = $this->getDirectoryOperator($this->base_dir . DIRECTORY_SEPARATOR . $path);

        if ($directory_iterator == null) {
            return $this->createForbiddenDirectoryContents();
        }

        foreach ($directory_iterator as $file_info) {
            if ($file_info->getFilename() === '.') {
                continue;
            }

            if ($file_info->isDot() && $remove_parent_directory_listing) {
                continue;
            }

            $current_item = new DirectoryItem(
                $file_info->getFilename(),
                $file_info->getType(),
                $file_info->getSize(),
                $file_info->getMTime()
            );

            $items = $this->addItemInRightSection($items, $current_item, $file_info);
        }

        return $this->createNaturalAlphabeticallyItemsCollection($items);
    }

    private function addItemInRightSection(array $items, DirectoryItem $current_item, $file_info)
    {
        if ($file_info->isDir()) {
            $items['folders'][$file_info->getFilename()] = $current_item;
        } else {
            $items['files'][$file_info->getFilename()] = $current_item;
        }

        return $items;
    }

    private function createNaturalAlphabeticallyItemsCollection(array $items)
    {
        uksort($items['folders'], "strnatcmp");
        uksort($items['files'], "strnatcmp");

        $folders = array_values($items['folders']);
        $files   = array_values($items['files']);

        return new DirectoryItemCollection($folders, $files);
    }

    private function getDirectoryOperator($path)
    {
        try {
            return new DirectoryIterator($path);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    private function createForbiddenDirectoryContents()
    {
        $parent_item = new DirectoryItem('..', 'dir', null, null);

        $directory_contents = new DirectoryItemCollection([$parent_item], []);
        $directory_contents->setAsForbidden();

        return $directory_contents;
    }
}
