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

class Tracker_XML_Exporter_InArchiveFilePathXMLExporter implements Tracker_XML_Exporter_FilePathXMLExporter
{

    /**
     *
     * @return string
     */
    public function getPath(Tracker_FileInfo $file_info)
    {
        return Tuleap\Project\XML\ArchiveInterface::DATA_DIR .
               DIRECTORY_SEPARATOR .
               Tracker_XML_Exporter_ArtifactAttachmentExporter::FILE_PREFIX .
               $file_info->getId();
    }
}
