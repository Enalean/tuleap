<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap;

/**
 * This is required when vfsStream cannot be used (ex. exec() commands & co).
 * In other cases vfsStream is a better option.
 */
trait TemporaryTestDirectory
{
    private $temporary_directory;

    /**
     * @before
     */
    protected function generateTemporaryDirectory(): void
    {
        if (!$this->temporary_directory) {
            do {
                $this->temporary_directory = '/tmp/tuleap_tests_' . \bin2hex(\random_bytes(16));
            } while (file_exists($this->temporary_directory));
        }
        if (! mkdir($concurrentDirectory = $this->temporary_directory, 0700, true) && ! is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * @after
     */
    protected function cleanupTemporaryDirectory(): void
    {
        if ($this->temporary_directory && file_exists($this->temporary_directory)) {
            $this->recurseDeleteInDir($this->temporary_directory);
            rmdir($this->temporary_directory);
        }
    }

    protected function getTmpDir(): string
    {
        return $this->temporary_directory;
    }

    /**
     * Recursive rm function.
     * see: http://us2.php.net/manual/en/function.rmdir.php#87385
     * Note: the function will empty everything in the given directory but won't remove the directory itself
     *
     * @param string $mypath Path to the directory
     *
     */
    private function recurseDeleteInDir($mypath): void
    {
        $mypath = rtrim($mypath, '/');
        $d      = opendir($mypath);
        if (! $d) {
            return;
        }
        while (($file = readdir($d)) !== false) {
            if ($file !== "." && $file !== "..") {
                $typepath = $mypath . "/" . $file;

                if (is_file($typepath) || is_link($typepath)) {
                    unlink($typepath);
                } else {
                    $this->recurseDeleteInDir($typepath);
                    rmdir($typepath);
                }
            }
        }
        closedir($d);
    }
}
