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
require_once __DIR__ . '/../../www/file/file_utils.php';

class FRSFileDao extends DataAccessObject
{
    /**
     * Return the array that match given id.
     *
     * @return DataAccessResult
     */
    public function searchById($id)
    {
        $_id = (int) $id;
        return $this->_search(' f.file_id = ' . $this->da->escapeInt($_id), '', ' ORDER BY release_time DESC LIMIT 1');
    }

    public function searchInReleaseById($id, $group_id)
    {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(
            ' p.group_id=' . $this->da->escapeInt($_group_id) . ' AND r.release_id = f.release_id' .
                              ' AND r.package_id = p.package_id AND f.file_id =' . $this->da->escapeInt($_id),
            '',
            'ORDER BY post_date DESC LIMIT 1',
            array('frs_package AS p', 'frs_release AS r')
        );
    }

    public function searchByIdList($idList)
    {
        if (is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' f.file_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of files for a given release according to filters
     *
     * @param int $id the ID of the release the files belong to
     * @param int $only_active_files 1 means that only files with an active status will be retrieved. 0 means all files
     * @return DataAccessResult
     */
    public function searchByReleaseId($id, $only_active_files = 1)
    {
        $_id = (int) $id;
        $where_status = "";
        if ($only_active_files == 1) {
            $where_status = " AND status='A' ";
        }
        return $this->_search(' release_id=' . $this->da->escapeInt($_id) . ' ' . $where_status, '', '');
    }


    public function searchInfoByGroupFileID($group_id, $file_id)
    {
        $_group_id = (int) $group_id;
        $_file_id = (int) $file_id;

        $sql = sprintf(
            "SELECT f.filename, f.file_id AS file_id, p.group_id AS group_id, " .
                        "p.package_id, r.release_id "
                        . "FROM frs_release AS r, frs_package AS p, frs_file AS f "
                        . "WHERE p.group_id= %s "
                        . "AND r.package_id = p.package_id "
                        . "AND f.release_id = r.release_id "
                      . "AND f.file_id=%s ",
            $this->da->quoteSmart($_group_id),
            $this->da->quoteSmart($_file_id)
        );
         return $this->retrieve($sql);
    }

    /**
     * Retrieve file info from database.
     *
     * @param int $release_id the ID of the release the files belong to
     * @param int $only_active_files 1 means that only files with an active status will be retrieved. 0 means all files
     */
    public function searchInfoFileByReleaseID($release_id, $only_active_files = 1)
    {
        $_release_id = (int) $release_id;

        $where_status = "";
        if ($only_active_files) {
            $where_status = " AND status='A' ";
        }

        $sql = sprintf(
            "SELECT frs_file.file_id AS file_id, frs_file.filename AS filename, frs_file.file_size AS file_size,"
                . "frs_file.release_time AS release_time, frs_file.type_id AS type, frs_file.processor_id AS processor,"
                . "frs_dlstats_filetotal_agg.downloads AS downloads , frs_file.computed_md5 AS computed_md5, frs_file.user_id AS user_id,"
                . "frs_file.comment AS comment "
                . "FROM frs_file "
                . "LEFT JOIN frs_dlstats_filetotal_agg ON frs_dlstats_filetotal_agg.file_id=frs_file.file_id "
                . "WHERE release_id=%s" . $where_status,
            $this->da->quoteSmart($_release_id)
        );
        return $this->retrieve($sql);
    }

    public function _search($where, $group = '', $order = '', $from = array())
    {
        $sql = 'SELECT f.* '
            . ' FROM frs_file AS f '
            . (count($from) > 0 ? ', ' . implode(', ', $from) : '')
            . (trim($where) != '' ? ' WHERE ' . $where . ' ' : '')
            . $group
            . $order;
        return $this->retrieve($sql);
    }

    public function searchFileByName($file_name, $group_id)
    {
        $_group_id = (int) $group_id;
        return $this->_search(
            ' p.group_id=' . $this->da->escapeInt($_group_id) . ' AND r.release_id = f.release_id' .
                              ' AND r.package_id = p.package_id AND filename=' . $this->da->quoteSmart($file_name) . ' AND f.status=\'A\'',
            '',
            '',
            array('frs_package AS p', 'frs_release AS r')
        );
    }

    public function searchFileByNameFromRelease($file_name, $release_id)
    {
        $file_name  = $this->da->quoteSmart('%/' . $this->getDa()->escapeLikeValue($file_name));
        $release_id = $this->da->quoteSmart($release_id);
        $sql = 'SELECT file_id'
            . ' from frs_file'
            . ' WHERE filename LIKE ' . $file_name
            . ' AND release_id = ' . $release_id
            . ' AND status = \'A\'';

        return $this->retrieve($sql);
    }

    /**
     * create a row in the table frs_file
     *
     * @return true or id(auto_increment) if there is no error
     */
    public function create(
        $file_name = null,
        $filepath = null,
        $release_id = null,
        $type_id = null,
        $processor_id = null,
        $release_time = null,
        $file_size = null,
        $reference_md5 = null,
        $post_date = null,
        $status = 'A'
    ) {
        $arg    = array();
        $values = array();

        if ($file_name !== null) {
            $arg[] = 'filename';
            $values[] = $this->da->quoteSmart($file_name);
        }

        if ($filepath !== null) {
            $arg[] = 'filepath';
            $values[] = $this->da->quoteSmart($filepath);
        }

        if ($release_id !== null) {
            $arg[] = 'release_id';
            $values[] = ($this->da->escapeInt($release_id));
        }

        if ($type_id !== null) {
            $arg[] = 'type_id';
            $values[] = ($this->da->escapeInt($type_id));
        }

        if ($processor_id !== null) {
            $arg[] = 'processor_id';
            $values[] = ($this->da->escapeInt($processor_id));
        }

        if ($reference_md5 !== null) {
            $arg[] = 'reference_md5';
            $values[] = $this->da->quoteSmart($reference_md5);
        }

        $arg[] = 'release_time';
        $values[] = ($this->da->escapeInt(time()));

        if ($file_size !== null) {
            $arg[] = 'file_size';
            $values[] = ($this->da->escapeInt($file_size));
        } else {
            $arg[] = 'file_size';
            $values[] = file_utils_get_size($file_name);
        }

        $arg[] = 'post_date';
        $values[] = ($this->da->escapeInt(time()));

        $arg[] = 'status';
        $values[] = $status;

        $sql = 'INSERT INTO frs_file'
            . '(' . implode(', ', $arg) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';
        return $this->_createAndReturnId($sql);
    }


    public function createFromArray($data_array)
    {
        $arg    = array();
        $values = array();
        $cols   = array('filename', 'filepath', 'release_id', 'type_id', 'processor_id', 'file_size', 'status', 'computed_md5', 'reference_md5', 'user_id', 'comment', 'release_time', 'post_date');
        foreach ($data_array as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO frs_file '
                . '(' . implode(', ', $arg) . ')'
                . ' VALUES (' . implode(', ', $values) . ')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
        }
    }


    public function _createAndReturnId($sql)
    {
        return $this->updateAndGetLastId($sql);
    }
    /**
     * Update a row in the table frs_file
     *
     * @return true if there is no error
     */
    public function updateById(
        $file_id,
        $file_name = null,
        $release_id = null,
        $type_id = null,
        $processor_id = null,
        $release_time = null,
        $file_size = null,
        $status = null
    ) {
        $argArray = array();

        if ($file_name !== null) {
            $argArray[] = 'file_name=' . $this->da->quoteSmart($file_name);
        }

        if ($release_id !== null) {
            $argArray[] = 'release_id=' . ($this->da->escapeInt($release_id));
        }

        if ($type_id !== null) {
            $argArray[] = 'type_id=' . ($this->da->escapeInt($type_id));
        }

        if ($processor_id !== null) {
            $argArray[] = 'processor_id=' . ($this->da->escapeInt($processor_id));
        }

        if ($release_time !== null) {
            $argArray[] = 'release_time=' . ($this->da->escapeInt($release_time));
        }

        if ($file_size !== null) {
            $argArray[] = 'file_size=' . ($this->da->escapeInt($file_size));
        }

        if ($status !== null) {
            $argArray[] = 'status=' . $this->da->quoteSmart($status);
        }

        $sql = 'UPDATE frs_file'
            . ' SET ' . implode(', ', $argArray)
            . ' WHERE file_id=' . ($this->da->escapeInt($file_id));

        $inserted = $this->update($sql);
        return $inserted;
    }

    public function updateFromArray($data_array)
    {
        $updated = false;
        $id = false;
        if (isset($data_array['file_id'])) {
            $file_id = $data_array['file_id'];
        }
        if ($file_id) {
            $dar = $this->searchById($file_id);
            if (!$dar->isError() && $dar->valid()) {
                $current = $dar->current();
                $set_array = array();
                foreach ($data_array as $key => $value) {
                    if ($key != 'id' && $key != 'post_date' && $value != $current[$key]) {
                        $set_array[] = $key . ' = ' . $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE frs_file'
                        . ' SET ' . implode(' , ', $set_array)
                        . ' WHERE file_id=' . $this->da->quoteSmart($file_id);
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $release_id in frs_file
     *
     * @param $file_id int
     *
     * @return true if there is no error
     */
    public function delete($file_id)
    {
        $sql = "UPDATE frs_file SET status='D' WHERE file_id=" . $this->da->escapeInt($file_id);
        $deleted = $this->update($sql);
        return $deleted;
    }

    /**
     * Log the file download action into the database
     *
     * @param FRSFile $file the FRSFile Object to log the download of
     * @param int $user_id the user that download the file
     * @return bool true if there is no error, false otherwise
     */
    public function logDownload($file, $user_id)
    {
        $sql = "INSERT INTO filedownload_log(user_id,filerelease_id,time) "
             . "VALUES ('" . $this->da->escapeInt($user_id) . "','" . $this->da->escapeInt($file->getFileID()) . "','" . $this->da->escapeInt(time()) . "')";
        return $this->update($sql);
    }

    /**
     * Return true if a download is already logged for the user since the given time
     *
     * @param int $fileId
     * @param int $userId
     * @param int $time
     *
     * @return bool
     */
    public function existsDownloadLogSince($fileId, $userId, $time)
    {
        $sql = 'SELECT NULL' .
               ' FROM filedownload_log' .
               ' WHERE user_id = ' . $this->da->escapeInt($userId) .
               ' AND filerelease_id = ' . $this->da->escapeInt($fileId) .
               ' AND time >= ' . $time .
               ' LIMIT 1';
        $dar = $this->retrieve($sql);
        return ($dar && !$dar->isError() && $dar->rowCount() !== 0);
    }

    /**
     * Retrieve all the files marked as deleted but not yet present in 'deleted' table
     *
     * @param $groupId
     *
     * @return DataAccessResult
     */
    public function searchStagingCandidates($groupId = 0)
    {
        $fields = '';
        $from   = '';
        $where  = '';
        if ($groupId != 0) {
            $fields .= ', rel.name as release_name, rel.status_id as release_status, rel.release_id';
            $fields .= ', pkg.name as package_name, pkg.status_id as package_status, pkg.package_id';
            $from   .= ' JOIN frs_release rel ON (f.release_id = rel.release_id)' .
                       ' JOIN frs_package pkg ON (rel.package_id = pkg.package_id)';
            $where  .= ' AND pkg.group_id = ' . $this->da->escapeInt($groupId);
            $where  .= ' AND rel.status_id != ' . FRSRelease::STATUS_DELETED;
        }
        $sql = 'SELECT f.* ' .
               $fields .
               ' FROM frs_file f LEFT JOIN frs_file_deleted d USING(file_id)' .
               $from .
               ' WHERE f.status = "D"' .
               ' AND d.file_id IS NULL' .
               $where;
        return $this->retrieve($sql);
    }

    /**
     * Retrieve all deleted files not purged yet after a given period of time
     *
     * @param int $time Timestamp of the date to start the search
     * @param int $groupId
     * @param int $offset
     * @param int $limit
     *
     * @return DataAccessResult
     */
    public function searchFilesToPurge($time, $groupId = 0, $offset = 0, $limit = 0)
    {
        $fields = '';
        $from   = '';
        $where  = '';
        if ($groupId != 0) {
            $fields .= ', rel.name as release_name, rel.status_id as release_status, rel.release_id';
            $fields .= ', pkg.name as package_name, pkg.status_id as package_status, pkg.package_id';
            $from   .= ' JOIN frs_release rel USING (release_id)' .
                       ' JOIN frs_package pkg USING (package_id)';
            $where  .= ' AND pkg.group_id = ' . $this->da->escapeInt($groupId);
            $where  .= ' AND rel.status_id != ' . FRSRelease::STATUS_DELETED;
        }
        $sql = 'SELECT file.* ' .
               $fields .
               ' FROM frs_file_deleted file' .
               $from .
               ' WHERE delete_date <= ' . $this->da->escapeInt($time) .
               ' AND purge_date IS NULL' .
               $where .
               ' ORDER BY delete_date DESC';
        return $this->retrieve($sql);
    }

    /**
     * Copy deleted entry in the dedicated table
     *
     * @param int $id FileId
     *
     * @return bool
     */
    public function setFileInDeletedList($id)
    {
        // Store file in deleted table
        $sql = 'INSERT INTO frs_file_deleted(file_id, filename, filepath, release_id, type_id, processor_id, release_time, file_size, post_date, status, computed_md5, reference_md5, user_id,delete_date)' .
               ' SELECT file_id, filename, filepath, release_id, type_id, processor_id, release_time, file_size, post_date, status, computed_md5, reference_md5, user_id,' . $this->da->escapeInt($_SERVER['REQUEST_TIME']) .
               ' FROM frs_file' .
               ' WHERE file_id = ' . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    /**
     * Set the date of the purge of a file
     *
     * @param int $id File id
     * @param int $time Timestamp of the deletion
     *
     * @return bool
     */
    public function setPurgeDate($id, $time)
    {
        $sql = 'UPDATE frs_file_deleted' .
               ' SET purge_date = ' . $this->da->escapeInt($time) .
               ' WHERE file_id = ' . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    /**
     * Restore file by updating its status and removing it from  frs_file_deleted
     *
     * @param int $id File id
     *
     * @return bool
     */
    public function restoreFile($id)
    {
        $sql = 'UPDATE frs_file SET status = "A" WHERE file_id = ' . $this->da->escapeInt($id);
        if ($this->update($sql)) {
            $sql = 'DELETE FROM frs_file_deleted WHERE file_id = ' . $this->da->escapeInt($id);
            return $this->update($sql);
        }
        return false;
    }

    /**
     * Retrieves all the documents marked to be restored
     *
     * @return DataAccessResult
     */
    public function searchFilesToRestore($groupId = null)
    {
        $fields = '';
        $from   = '';
        $where  = '';
        if ($groupId !== null) {
            $fields .= ', rel.name as release_name, rel.status_id as release_status, rel.release_id';
            $fields .= ', pkg.name as package_name, pkg.status_id as package_status, pkg.package_id';
            $from   .= ' JOIN frs_release rel USING (release_id)' .
                       ' JOIN frs_package pkg USING (package_id)';
            $where  .= ' AND pkg.group_id = ' . $this->da->escapeInt($groupId);
        }
        $sql = 'SELECT file.* ' .
               $fields .
               ' FROM frs_file_deleted file' .
               $from .
               ' WHERE delete_date IS NULL ' .
               ' AND purge_date IS NULL' .
               $where;
        return $this->retrieve($sql);
    }

    /**
     * Returns if the file is already marked to be restored or not
     *
     * @param String $filename
     *
     * @return bool
     */
    public function isMarkedToBeRestored($filename)
    {
        $sql = 'SELECT NULL' .
               ' FROM frs_file_deleted file' .
               ' WHERE delete_date IS NULL ' .
               ' AND purge_date IS NULL ' .
               ' AND filename =' . $this->da->quoteSmart($filename);
        $res = $this->retrieve($sql);
        return ($res && !$res->isError() && $res->rowCount() > 0);
    }

    /**
     * Mark file to be restored
     *
     * @param int $id
     *
     * @return bool
     */
    public function markFileToBeRestored($id)
    {
                $sql = 'UPDATE frs_file_deleted AS f,' .
                ' frs_release AS r ' .
                ' SET f.delete_date = NULL ' .
                ' WHERE f.file_id = ' . $this->da->escapeInt($id) .
                ' AND f.release_id = r.release_id ' .
                ' AND r.status_id != 2 ';
        return $this->update($sql);
    }

    /**
     * Cancel restoration of a file
     *
     * @param int $fileId File id
     *
     * @return bool
     */
    public function cancelRestore($fileId)
    {
        $sql = 'UPDATE frs_file_deleted SET delete_date = ' . $this->da->escapeInt($_SERVER['REQUEST_TIME']) . ' WHERE file_id = ' . $this->da->escapeInt($fileId);
        return $this->update($sql);
    }

    /**
     * Insert the computed md5sum value in case of offline checksum comput
     * e
     * @param int $fileId
     * @param String $md5Computed
     *
     * @return bool
     */
    public function updateComputedMd5sum($fileId, $md5Computed)
    {
        $sql = ' UPDATE frs_file ' .
               ' SET computed_md5 = ' . $this->da->quoteSmart($md5Computed) .
               ' WHERE file_id= ' . $this->da->escapeInt($fileId);
        return $this->update($sql);
    }
}
