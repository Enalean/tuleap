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
 * I am responsible of reading the content of a zip archive to import artifacts history
 */
class Tracker_Artifact_XMLImport_XMLImportZipArchive {

    /** @var ZipArchive */
    private $zip;

    /** @var string */
    private $extraction_path;

    public function __construct(Tracker $tracker, ZipArchive $zip, $extraction_path){
        $this->zip             = $zip;
        $this->extraction_path = $this->tempdir($extraction_path, $tracker->getId());
    }

    /**
     * @return string The xml content of artifacts.xml in the zip archive
     */
    public function getXML() {
        return $this->zip->getFromName('artifacts.xml');
    }

    /**
     * @return string
     */
    public function getExtractionPath() {
        return $this->extraction_path;
    }

    /**
     * @return bool
     */
    public function extractFiles() {
        return $this->zip->extractTo($this->extraction_path);
    }

    /**
     * Create a temporary directory
     *
     * @see http://php.net/tempnam
     *
     * @return string Path to the new directory
     */
    private function tempdir($prefix, $tracker_id) {
        $template = 'import_tv5_'. $tracker_id .'_XXXXXX';

        return trim(`mktemp -d -p $prefix $template`);
    }

    public function cleanUp() {
        `rm -rf $this->extraction_path`;
    }
}
