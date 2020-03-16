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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_XML_Updater_TemporaryFileCreator
{

    /** @var string */
    private $copy_directory;

    /**
     * @param string $copy_directory
     */
    public function __construct()
    {
        $this->copy_directory = $this->getUniqueRandomDirectory();
    }

    public function __destruct()
    {
        $this->deleteTemporaryDirectory();
    }

    /**
     * @param string $path of original file
     *
     * @return string path of temporary copy
     */
    public function createTemporaryFile($path)
    {
        $temporary_file_name = $this->copy_directory . '/' . basename($path);
        copy($path, $temporary_file_name);

        return $temporary_file_name;
    }

    public function getTemporaryDirectory()
    {
        return $this->copy_directory;
    }

    private function getUniqueRandomDirectory()
    {
        $tmp = ForgeConfig::get('tmp_dir');
        return exec("mktemp -d -p $tmp copy-artifactXXXXXX");
    }

    private function deleteTemporaryDirectory()
    {
        if (! $this->copy_directory) {
            return;
        }

        $recursive_iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->copy_directory
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($recursive_iterator as $path) {
            if (in_array($path->getFilename(), array('.', '..'))) {
                continue;
            }

            if ($path->isDir()) {
                rmdir($path->getPathname());
            } else {
                unlink($path->getPathname());
            }
        }
        rmdir($this->copy_directory);
    }
}
