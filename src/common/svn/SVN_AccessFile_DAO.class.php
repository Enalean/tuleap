<?php
/**
 * Copyright (c) Enalean SAS 2014 - 2018. All rights reserved
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

class SVN_AccessFile_DAO extends DataAccessObject
{

    private function addNewVersion($group_id, $content)
    {
        $new_version_number  = 0;
        $last_version_number = $this->getLastVersionNumber($group_id);
        $current_timestamp   = $this->da->escapeInt($_SERVER['REQUEST_TIME']);

        if ($last_version_number) {
            $new_version_number = $last_version_number + 1;
        }

        $sql = "INSERT INTO svn_accessfile_history (
                    version_number,
                    group_id,
                    content,
                    version_date
                )
                VALUES (
                    $new_version_number,
                    $group_id,
                    $content,
                    $current_timestamp
                )";

        $result = $this->updateAndGetLastId($sql);

        if (! $result) {
            throw new SVN_SQLRequestNotSuccededException();
        }

        return $result;
    }

    public function updateAccessFileVersionInProject($group_id, $version_id)
    {
        try {
            return $this->linkNewVersionIdToProject($group_id, $version_id);
        } catch (SVN_SQLRequestNotSuccededException $exception) {
            return false;
        }
    }

    public function saveNewAccessFileVersionInProject($group_id, $content)
    {
        try {
            $this->startTransaction();

            $group_id = $this->da->escapeInt($group_id);
            $content  = $this->da->quoteSmart($content);

            $version_id = $this->addNewVersion($group_id, $content);
            $this->linkNewVersionIdToProject($group_id, $version_id);

            $this->commit();
            return true;
        } catch (SVN_SQLRequestNotSuccededException $exception) {
            $this->rollBack();
            return false;
        }
    }

    public function getLastVersionNumber($group_id)
    {
        $sql = "SELECT max(version_number) as version_number
                FROM svn_accessfile_history
                WHERE group_id = $group_id";

        $result = $this->retrieve($sql);

        if (! $result) {
            return null;
        }

        $row = $result->getRow();
        return $row['version_number'];
    }

    private function linkNewVersionIdToProject($group_id, $version_id)
    {
        $sql = "UPDATE groups
                SET svn_accessfile_version_id = $version_id
                WHERE group_id = $group_id";

        $result = $this->update($sql);

        if (! $result) {
            throw new SVN_SQLRequestNotSuccededException();
        }

        return $result;
    }
    public function getAllVersions($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT version_number, id, version_date, content
                FROM svn_accessfile_history
                WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function getCurrentVersionNumber($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT s.version_number
                FROM svn_accessfile_history s
                    JOIN groups g ON g.group_id = s.group_id
                WHERE g.group_id = $group_id
                    AND g.svn_accessfile_version_id = s.id";

        $result = $this->retrieve($sql);

        if (! $result) {
            return null;
        }

        $row = $result->getRow();

        return $row['version_number'];
    }

    public function getVersionContent($version_id)
    {
        $version_id = $this->da->escapeInt($version_id);

        $sql = "SELECT content
                FROM svn_accessfile_history
                WHERE id = $version_id";

        $result = $this->retrieve($sql);

        if (! $result) {
            return null;
        }

        $row = $result->getRow();

        return $row['content'];
    }
}
