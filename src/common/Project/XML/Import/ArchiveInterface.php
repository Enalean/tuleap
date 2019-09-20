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

interface ArchiveInterface extends \Tuleap\Project\XML\ArchiveInterface
{

    /**
     * Returns project.xml content
     */
    public function getProjectXML();

    /**
     * Returns users.xml content
     */
    public function getUsersXML();

    /**
     * Extrac archive files
     */
    public function extractFiles();

    /**
     * Return where the files are extracted
     */
    public function getExtractionPath();

    /**
     * Delete everythin in the temporary extraction path
     */
    public function cleanUp();
}
