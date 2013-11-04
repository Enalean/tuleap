<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_REST_Artifact_FileInfoRepresentation {
    /** @var int ID of the file */
    public $id;

    /** @var int ID of the user who created the file */
    public $submitted_by;

    /** @var string Description of the file*/
    public $description;

    /** @var string Name of the file */
    public $filename;

    /** @var int Size of the file in bytes */
    public $filesize;

    /** @var string Mime type */
    public $filetype;

    public function __construct($id, $submitted_by, $description, $filename, $filesize, $filetype) {
        $this->id           = $id;
        $this->submitted_by = $submitted_by;
        $this->description  = $description;
        $this->filename     = $filename;
        $this->filesize     = $filesize;
        $this->filetype     = $filetype;
    }
}
