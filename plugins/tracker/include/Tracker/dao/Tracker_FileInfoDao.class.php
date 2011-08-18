<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for Tracker_FileInfo
 */
 
require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_FileInfoDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_fileinfo';
    }
    
    /**
    * Searches Tracker_FileInfo by Id 
    * @return DataAccessResult
    */
    function searchById($id) {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * 
                FROM $this->table_name 
                WHERE id = $id";
        return $this->retrieve($sql);
    }
    
    public function create($submitted_by, $description, $filename, $filesize, $filetype) {
        $submitted_by = $this->da->escapeInt($submitted_by);
        $description  = $this->da->quoteSmart($description);
        $filename     = $this->da->quoteSmart($filename);
        $filesize     = $this->da->escapeInt($filesize);
        $filetype     = $this->da->quoteSmart($filetype);
        $sql = "INSERT INTO $this->table_name(submitted_by, description, filename, filesize, filetype)
                VALUES ($submitted_by, $description, $filename, $filesize, $filetype)";
        return $this->updateAndGetLastId($sql);
    }
}
?>