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

namespace Tuleap\Project\XML\Import;

use Tuleap\Project\XML\ArchiveException;

class ZipArchive implements ArchiveInterface
{

    /** @var \ZipArchive */
    private $archive;
    private $archive_path;
    private $extraction_path;

    public function __construct($archive_path, $tmpdir)
    {
        $this->archive_path    = $archive_path;
        $this->extraction_path = $this->tempdir($tmpdir);
        $this->archive         = new \ZipArchive();
        if ($this->archive->open($this->archive_path) !== true) {
            throw new ArchiveException('Cannot open zip archive: ' . $archive_path);
        }
    }

    public function __destruct()
    {
        $this->archive->close();
    }

    public function extractFiles()
    {
        $this->archive->extractTo($this->extraction_path);
    }

    public function getExtractionPath()
    {
        return $this->extraction_path;
    }

    /**
     * Create a temporary directory
     *
     * @see http://php.net/tempnam
     *
     * @return string Path to the new directory
     */
    private function tempdir($tmp_dir)
    {
        $template = 'import_project_XXXXXX';

        return trim(`mktemp -d -p $tmp_dir $template`);
    }

    public function cleanUp()
    {
        exec("rm -rf $this->extraction_path");
    }

    public function getProjectXML()
    {
        return $this->archive->getFromName(self::PROJECT_FILE);
    }

    public function getUsersXML()
    {
        return $this->archive->getFromName(self::USER_FILE);
    }
}
