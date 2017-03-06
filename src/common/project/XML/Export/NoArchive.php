<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class NoArchive implements ArchiveInterface
{

    public function __construct()
    {
    }

    public function close()
    {
    }

    public function addEmptyDir($dirname)
    {
    }

    public function addFile($localname, $path_to_filesystem)
    {
    }

    public function addFromString($localname, $contents)
    {
    }

    public function getArchivePath()
    {
    }

    public function isADirectory()
    {
    }
}
