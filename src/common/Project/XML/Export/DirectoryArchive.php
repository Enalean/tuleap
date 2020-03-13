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

use Tuleap\Project\XML\ArchiveException;

class DirectoryArchive implements ArchiveInterface
{
    private $archive_path;

    public function __construct($archive_path)
    {
        $this->archive_path = $archive_path;
        if (file_exists($this->archive_path)) {
            throw new ArchiveException("Unable to create directory {$this->archive_path} for archive: file already exist");
        }
        mkdir($this->archive_path, 0700, true);
    }

    public function close()
    {
    }

    public function addEmptyDir($dirname)
    {
        if (! is_dir($this->archive_path . DIRECTORY_SEPARATOR . $dirname)) {
            return mkdir($this->archive_path . DIRECTORY_SEPARATOR . $dirname, 0700);
        }
    }

    public function addFile($localname, $path_to_filesystem)
    {
        return copy($path_to_filesystem, $this->archive_path . DIRECTORY_SEPARATOR . $localname);
    }

    public function addFromString($localname, $contents)
    {
        file_put_contents($this->archive_path . DIRECTORY_SEPARATOR . $localname, $contents);
    }

    public function getArchivePath()
    {
        return $this->archive_path;
    }

    public function isADirectory()
    {
        return true;
    }
}
