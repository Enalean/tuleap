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

class DirectoryArchive implements ArchiveInterface
{

    private $archive_path;

    public function __construct($archive_path)
    {
        $this->archive_path = $archive_path;
    }

    public function extractFiles()
    {
        // nothing to do
    }

    public function cleanUp()
    {
        // nothing to do
    }

    public function getExtractionPath()
    {
        return $this->archive_path;
    }

    public function getProjectXML()
    {
        return file_get_contents($this->archive_path . DIRECTORY_SEPARATOR . self::PROJECT_FILE);
    }

    public function getUsersXML()
    {
        return file_get_contents($this->archive_path . DIRECTORY_SEPARATOR . self::USER_FILE);
    }
}
