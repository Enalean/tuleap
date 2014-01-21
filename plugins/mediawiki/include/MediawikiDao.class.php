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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class MediawikiDao extends DataAccessObject {

    public function getMediawikiPagesNumberOfAProject(Project $project) {
        $database_name = self::getMediawikiDatabaseName($project);
        $group_id      = $this->da->escapeInt($project->getID());

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM $database_name.mwpage";

        return $this->retrieve($sql)->getRow();
    }

    public function getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate(Project $project, $start_date, $end_date) {
        $database_name = self::getMediawikiDatabaseName($project);
        $group_id      = $this->da->escapeInt($project->getID());

        $start_date    = date("YmdHis", strtotime($start_date));
        $end_date      = date("YmdHis", strtotime($end_date));

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM $database_name.mwpage
                WHERE
                    page_touched >= $start_date
                    AND
                    page_touched <= $end_date
               ";

        return $this->retrieve($sql)->getRow();
    }

    public function getCreatedPagesNumberSinceStartDate(Project $project, $start_date) {
        $database_name = self::getMediawikiDatabaseName($project);
        $group_id      = $this->da->escapeInt($project->getID());

        $start_date    = date("YmdHis", strtotime($start_date));

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM $database_name.mwrevision
                WHERE
                    rev_parent_id=0
                    AND
                    rev_timestamp >= $start_date
               ";

        return $this->retrieve($sql)->getRow();
    }

    public function getMediawikiGroupsForUser(PFUser $user, Project $project) {
        $database_name = self::getMediawikiDatabaseName($project);
        $user_name     = $this->da->quoteSmart($this->getMediawikiUserName($user));

        $sql = "SELECT ug_group
                FROM $database_name.mwuser_groups
                    INNER JOIN $database_name.mwuser ON $database_name.mwuser.user_id = $database_name.mwuser_groups.ug_user
                WHERE user_name = $user_name";

        return $this->retrieve($sql)->getRow();
    }

    private function getMediawikiUserName(PFUser $user) {
        return ucfirst($user->getUnixName());
    }

    public static function getMediawikiDatabaseName(Project $project) {
        return str_replace ('-', '_', "plugin_mediawiki_". $project->getUnixName());
    }
}

