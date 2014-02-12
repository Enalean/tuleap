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
        $user_name     = $this->da->quoteSmart($user->getUnixName());

        $sql = "SELECT ug_group
                FROM $database_name.mwuser_groups
                    INNER JOIN $database_name.mwuser ON $database_name.mwuser.user_id = $database_name.mwuser_groups.ug_user
                WHERE user_name LIKE $user_name";

        return $this->retrieve($sql)->getRow();
    }

    public function removeUser(PFUser $user, Project $project) {
        $database_name   = self::getMediawikiDatabaseName($project);
        $user_id         = $this->getMediawikiUserId($user, $project);
        $escaped_user_id = $this->da->escapeInt($user_id);

        if (! $user_id) {
            return false;
        }

        $this->removeAllUserGroups($escaped_user_id, $database_name);

        $sql = "DELETE
                FROM $database_name.mwuser
                WHERE user_id = $escaped_user_id";

        return $this->update($sql);
    }

    private function removeAllUserGroups($escaped_user_id, $database_name) {
        $sql = "DELETE
                FROM $database_name.mwuser_groups
                WHERE ug_user = $escaped_user_id";

        return $this->update($sql);
    }

    public function removeAdminsGroupsForUser(PFUser $user, Project $project) {
        $database_name   = self::getMediawikiDatabaseName($project);
        $user_id         = $this->getMediawikiUserId($user, $project);
        $escaped_user_id = $this->da->escapeInt($user_id);

        if (! $user_id) {
            return false;
        }

         $sql = "DELETE
                 FROM $database_name.mwuser_groups
                 WHERE ug_user = $escaped_user_id
                   AND ug_group = 'bureaucrat'
                   OR  ug_group = 'sysop'";

        return $this->update($sql);
    }

    public function renameUser(Project $project, $old_user_name, $new_user_name) {
        $database_name = self::getMediawikiDatabaseName($project);
        $old_user_name = $this->da->quoteSmart($this->getMediawikiUserName($old_user_name));
        $new_user_name = $this->da->quoteSmart($this->getMediawikiUserName($new_user_name));

        $sql = "UPDATE $database_name.mwuser
                SET user_name = $new_user_name
                WHERE user_name = $old_user_name";

        $this->update($sql);

        $sql = "UPDATE $database_name.mwrecentchanges
                SET rc_user_text = $new_user_name
                WHERE rc_user_text = $old_user_name";

        $this->update($sql);

        $sql = "UPDATE $database_name.mwrevision
                SET rev_user_text = $new_user_name
                WHERE rev_user_text = $old_user_name";

        return $this->update($sql);
    }

    private function getMediawikiUserId(PFUser $user, Project $project) {
        $database_name = self::getMediawikiDatabaseName($project);
        $user_name     = $this->da->quoteSmart($user->getUnixName());

        $sql = "SELECT user_id
                FROM $database_name.mwuser
                WHERE user_name LIKE $user_name";

        $data = $this->retrieve($sql)->getRow();

        if (! $data) {
            return false;
        }

        return $data['user_id'];
    }

    public function getMediawikiUserGroupMapping(Project $project) {
        $group_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT ugroup_id, mw_group_name
                FROM plugin_mediawiki_ugroup_mapping
                WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function addMediawikiUserGroupMapping(Project $project, $unchecked_mw_group_name, $unchecked_param_ugroup_id) {
        $group_id = $this->da->escapeInt($project->getID());
        $ugroup_id = $this->da->escapeInt($unchecked_param_ugroup_id);
        $mw_group_name = $this->da->quoteSmart($unchecked_mw_group_name);

        $sql = "INSERT INTO plugin_mediawiki_ugroup_mapping (group_id, mw_group_name, ugroup_id)
                VALUES ($group_id, $mw_group_name, $ugroup_id)";
        return $this->update($sql);
    }

    public function removeMediawikiUserGroupMapping(Project $project, $unchecked_mw_group_name, $unchecked_ugroup_id) {
        $group_id = $this->da->quoteSmart($project->getID());
        $ugroup_id = $this->da->escapeInt($unchecked_ugroup_id);
        $mw_group_name = $this->da->quoteSmart($unchecked_mw_group_name);

        $sql = "DELETE FROM plugin_mediawiki_ugroup_mapping
                WHERE group_id = $group_id AND ugroup_id = $ugroup_id AND mw_group_name = $mw_group_name";
        return $this->update($sql);
    }

    /**
     * Converts a Tuleap username into a Mediawiki username
     * The mediawiki username has his first char uppercase
     * and replace the underscore by a space
     *
     * This behaviour is define in LocalSettings.php with User::newFromName($username);
     *
     */
    private function getMediawikiUserName($user_name) {
        $user_name_with_first_char_uppercase = ucfirst($user_name);

        return str_replace ('_', ' ', $user_name_with_first_char_uppercase);
    }

    public static function getMediawikiDatabaseName(Project $project) {
        return str_replace ('-', '_', "plugin_mediawiki_". $project->getUnixName());
    }
}

