<?php

/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Tracker_Artifact_Attachment_TemporaryFileManagerDao extends DataAccessObject {

    public function create($user_id, $name, $description, $mimetype, $timestamp, $tempname) {
        $user_id     = $this->da->escapeInt($user_id);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $mimetype    = $this->da->quoteSmart($mimetype);
        $timestamp   = $this->da->escapeInt($timestamp);
        $tempname    = $this->da->quoteSmart($tempname);

        $this->startTransaction();

        $sql = "INSERT INTO tracker_fileinfo
                    (submitted_by, description, filename, filetype)
                VALUES ($user_id, $description, $name, $mimetype)";

        $file_id = $this->updateAndGetLastId($sql);

        if (! $file_id) {
            $this->rollBack();
            return false;
        }

        $sql = "INSERT INTO tracker_fileinfo_temporary
                    (fileinfo_id, last_modified, created, tempname)
                VALUES ($file_id, $timestamp, $timestamp, $tempname)";

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return $file_id;
    }

    public function updateLastModifiedDate($file_id, $last_modified) {
        $file_id       = $this->da->escapeInt($file_id);
        $last_modified = $this->da->escapeInt($last_modified);

        $sql = "UPDATE tracker_fileinfo_temporary
                    SET last_modified = $last_modified
                WHERE fileinfo_id = '$file_id'";

        return $this->update($sql);
    }

}
?>
