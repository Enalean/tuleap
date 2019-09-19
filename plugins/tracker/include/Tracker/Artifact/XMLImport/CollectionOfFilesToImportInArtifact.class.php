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

class Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact
{

    /** @var array */
    private $history;

    /** @var array */
    private $files;


    public function __construct(SimpleXMLElement $artifact_xml)
    {
        $this->history = array();
        $this->files   = $this->extractFilesFromXML($artifact_xml);
    }

    public function extractFilesFromXML(SimpleXMLElement $artifact_xml)
    {
        $files     = array();
        $files_xml = $artifact_xml->file;

        foreach ($files_xml as $file) {
            $file_attributes = $file->attributes();
            $file_id         = (string) $file_attributes['id'];
            $files[$file_id] = $file;
        }

        return $files;
    }

    public function getFileXML($file_id)
    {
        return $this->files[$file_id];
    }

    public function markAsImported($file_id)
    {
        if ($this->fileIsAlreadyImported($file_id)) {
            return;
        }

        $this->history[] = $file_id;
    }

    public function fileIsAlreadyImported($file_id)
    {
        return in_array($file_id, $this->history);
    }
}
