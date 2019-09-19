<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\Project\XML\Export;

interface ArchiveInterface extends \Tuleap\Project\XML\ArchiveInterface
{

    /**
     * Finalize archive
     */
    public function close();

    /**
     * Create an empty directory where data will be stored
     *
     * @param string $dirname
     */
    public function addEmptyDir($dirname);

    /**
     * Copy a local file from the files ystem to the archive
     *
     * @param string $localname          Name inside the archive
     * @param string $path_to_filesystem Path on the file system
     */
    public function addFile($localname, $path_to_filesystem);

    /**
     * Add content inside a file of the archive
     *
     * @param string $localname Name inside the archive
     * @param string $contents  Stuff to add
     */
    public function addFromString($localname, $contents);

    public function getArchivePath();

    public function isADirectory();
}
