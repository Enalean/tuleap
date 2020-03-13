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

class ZipArchive implements ArchiveInterface
{
    /**
     * @var \ZipArchive
     */
    private $archive;
    private $archive_path;

    public function __construct($archive_path)
    {
        $this->archive      = new \ZipArchive();
        $this->archive_path = $archive_path;

        if ($this->archive->open($this->archive_path, \ZipArchive::CREATE) !== true) {
            throw new ArchiveException('Cannot create zip archive: ' . $this->archive_path);
        }
    }

    public function close()
    {
        if (! $this->archive->close()) {
            throw new ArchiveException("Unable to close zip archive: " . $this->archive->getStatusString());
        }
    }

    public function addEmptyDir($dirname)
    {
        $this->archive->addEmptyDir($dirname);
    }

    public function addFile($localname, $path_to_filesystem)
    {
        if (! $this->archive->addFile($path_to_filesystem, $localname)) {
            throw new ArchiveException("Unable to add $localname into archive: " . $this->archive->getStatusString());
        }
    }

    public function addFromString($localname, $contents)
    {
        if (! $this->archive->addFromString($localname, $contents)) {
            throw new ArchiveException("Unable to add $localname into archive: " . $this->archive->getStatusString());
        }
    }

    public function getArchivePath()
    {
        return $this->archive_path;
    }

    public function isADirectory()
    {
        return false;
    }
}
