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

/**
 * I am responsible of reading the content of a zip archive to import artifacts history
 */
class ProjectXMLImporter_XMLImportZipArchive extends XMLImportZipArchive {

    const RESOURCE_NAME        = 'project';
    const PROJECT_XML_FILENAME = "project.xml";

    public function __construct(Project $project, ZipArchive $zip, $extraction_path){
        parent::__construct($zip);

        $this->extraction_path = $this->tempdir($extraction_path, $project->getID());
    }

    /**
     * @return string The xml content of artifacts.xml in the zip archive
     */
    public function getXML() {
        return $this->zip->getFromName(self::PROJECT_XML_FILENAME);
    }

    /**
     * Create a temporary directory
     *
     * @see http://php.net/tempnam
     *
     * @return string Path to the new directory
     */
    protected function tempdir($tmp_dir, $project_id) {
        return parent::tempdir($tmp_dir, self::RESOURCE_NAME, $project_id);
    }
}
