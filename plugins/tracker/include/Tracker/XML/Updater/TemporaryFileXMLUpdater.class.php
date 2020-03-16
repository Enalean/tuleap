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

/**
 * I change the xml file elements to target a temporary file instead of real file
 * (so that it is not moved during the import)
 */
class Tracker_XML_Updater_TemporaryFileXMLUpdater
{

    /**
     * @var Tracker_XML_Updater_TemporaryFileCreator
     */
    private $temporary_file_creator;

    public function __construct(
        Tracker_XML_Updater_TemporaryFileCreator $temporary_file_creator
    ) {
        $this->temporary_file_creator = $temporary_file_creator;
    }

    public function update(SimpleXMLElement $xml_artifact)
    {
        foreach ($xml_artifact->file as $file) {
            $path = (string) $file->path;
            $temporary_path = $this->temporary_file_creator->createTemporaryFile($path);
            $file->path = $temporary_path;
        }
    }
}
