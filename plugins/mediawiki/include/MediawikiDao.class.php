<?php
/**
 * Copyright (c) Enalean, 2013, 2015. All Rights Reserved.
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
        $database_name = $this->getMediawikiDatabaseName($project);
        $group_id      = $this->da->escapeInt($project->getID());

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM $database_name.mwpage";

        return $this->retrieve($sql)->getRow();
    }

    public function getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate(Project $project, $start_date, $end_date) {
        $database_name = $this->getMediawikiDatabaseName($project);
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
        $database_name = $this->getMediawikiDatabaseName($project);
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
        $database_name = $this->getMediawikiDatabaseName($project);
        $user_name     = $this->da->quoteSmart($this->getMediawikiUserName($user->getUnixName()));

        $sql = "SELECT ug_group
                FROM $database_name.mwuser_groups
                    INNER JOIN $database_name.mwuser ON $database_name.mwuser.user_id = $database_name.mwuser_groups.ug_user
                WHERE user_name = $user_name";

        return $this->retrieve($sql);
    }

    public function removeUser(PFUser $user, Project $project) {
        $database_name   = $this->getMediawikiDatabaseName($project);
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
        $database_name   = $this->getMediawikiDatabaseName($project);
        $user_id         = $this->getMediawikiUserId($user, $project);
        $escaped_user_id = $this->da->escapeInt($user_id);

        if (! $user_id) {
            return false;
        }

         $sql = "DELETE
                 FROM $database_name.mwuser_groups
                 WHERE ug_user = $escaped_user_id
                   AND ug_group IN ('bureaucrat', 'sysop')";

        return $this->update($sql);
    }

    public function renameUser(Project $project, $old_user_name, $new_user_name) {
        $database_name = $this->getMediawikiDatabaseName($project);
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
        $database_name = $this->getMediawikiDatabaseName($project);
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

    public function getMediawikiGroupsMappedForUGroups(PFUser $user, Project $project) {
        $group_id   = $this->da->escapeInt($project->getID());
        $ugroup_ids = $this->da->escapeIntImplode($user->getUgroups($project->getID(), null));

        $sql = "SELECT DISTINCT tuleap_mwgroups.real_name
                FROM plugin_mediawiki_ugroup_mapping AS ugroup_mapping
                    JOIN plugin_mediawiki_tuleap_mwgroups AS tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE ugroup_mapping.group_id = $group_id
                AND ugroup_mapping.ugroup_id IN ($ugroup_ids)";
        return $this->retrieve($sql);
    }

    public function getAllMediawikiGroups($project) {
        $group_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT DISTINCT tuleap_mwgroups.real_name
                FROM plugin_mediawiki_ugroup_mapping AS ugroup_mapping
                    JOIN plugin_mediawiki_tuleap_mwgroups AS tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE ugroup_mapping.group_id = $group_id";
        return $this->retrieve($sql);
    }

    public function deleteUserGroup($group_id, $ugroup_id) {
        $group_id  = $this->da->escapeInt($group_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE FROM plugin_mediawiki_ugroup_mapping
                WHERE group_id = $group_id
                AND ugroup_id = $ugroup_id";
        return $this->update($sql);
    }

    /**
     * Reset all user permissions for a given database according to mapping
     *
     * @param Project $project
     * @return boolean
     */
    public function resetUserGroups(Project $project) {
        $database_name = $this->getMediawikiDatabaseName($project);
        $group_id      = $this->da->escapeInt($project->getID());

        $this->update("TRUNCATE TABLE $database_name.mwuser_groups");
        return $this->feedMediawikiUserGroupsWithTuleapMapping($database_name, $group_id, 0);
    }

    public function resetUserGroupsForUser(PFUser $user, Project $project) {
        $database_name  = $this->getMediawikiDatabaseName($project);
        $group_id       = $this->da->escapeInt($project->getID());
        $forge_user_id  = $this->da->escapeInt($user->getId());
        $user_name      = $this->da->quoteSmart($this->getMediawikiUserName($user->getUnixName()));

        $this->deleteUserGroupsForUser($database_name, $user_name);
        $this->feedMediawikiUserGroupsWithTuleapMapping($database_name, $group_id, $forge_user_id);
    }

    private function deleteUserGroupsForUser($database_name, $user_name) {
        return $this->update("DELETE $database_name.mwuser_groups
                              FROM $database_name.mwuser_groups
                                JOIN $database_name.mwuser ON ($database_name.mwuser.user_id = $database_name.mwuser_groups.ug_user)
                              WHERE $database_name.mwuser.user_name = $user_name");
    }

    private function feedMediawikiUserGroupsWithTuleapMapping($database_name, $group_id, $forge_user_id) {
        $sql = "
            INSERT INTO $database_name.mwuser_groups(ug_user, ug_group)
                  (".$this->getSQLMediawikiGroupsThatMatchStaticGroups($database_name, $group_id, $forge_user_id).")
            UNION (".$this->getSQLMediawikiGroupsThatMatchProjectAdmins($database_name, $group_id, $forge_user_id).")
            UNION (".$this->getSQLMediawikiGroupsThatMatchProjectMembers($database_name, $group_id, $forge_user_id).")
            UNION (".$this->getSQLMediawikiGroupsThatMatchRegisteredUsers($database_name, $group_id, $forge_user_id).")
            UNION (".$this->getSQLMediawikiGroupsThatMatchAnonymousUsers($database_name, $group_id, $forge_user_id).")";
        return $this->update($sql);
    }

    private function getSQLMediawikiGroupsThatMatchStaticGroups($database_name, $group_id, $forge_user_id) {
        $sql = "SELECT mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $database_name.mwuser
                    JOIN user ON (user.user_name = REPLACE(mwuser.user_name, ' ', '_'))
                    JOIN ugroup_user ON (ugroup_user.user_id = user.user_id)
                    JOIN ugroup ON (ugroup.ugroup_id = ugroup_user.ugroup_id AND ugroup.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.ugroup_id = ugroup_user.ugroup_id)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchProjectAdmins($database_name, $group_id, $forge_user_id) {
        $sql = "SELECT mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $database_name.mwuser
                    JOIN user ON (user.user_name = REPLACE(mwuser.user_name, ' ', '_'))
                    JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = user_group.group_id AND ugroup_mapping.ugroup_id = 4 AND user_group.admin_flags='A')
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchProjectMembers($database_name, $group_id, $forge_user_id) {
        $sql = "SELECT mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $database_name.mwuser
                    JOIN user ON (user.user_name = REPLACE(mwuser.user_name, ' ', '_'))
                    JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = user_group.group_id AND ugroup_mapping.ugroup_id = 3)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchRegisteredUsers($database_name, $group_id, $forge_user_id) {
        $sql = "SELECT mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $database_name.mwuser
                    JOIN user ON (user.user_name = REPLACE(mwuser.user_name, ' ', '_'))
                    LEFT JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = $group_id AND ugroup_mapping.ugroup_id = 2)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE user_group.user_id IS NULL";
        if ($forge_user_id != 0) {
            $sql .= " AND user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchAnonymousUsers($database_name, $group_id, $forge_user_id) {
        $sql = "SELECT mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $database_name.mwuser
                    JOIN user ON (user.user_name = REPLACE(mwuser.user_name, ' ', '_'))
                    LEFT JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = $group_id AND ugroup_mapping.ugroup_id = 1)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE user_group.user_id IS NULL";
        if ($forge_user_id != 0) {
            $sql .= " AND user.user_id = .$forge_user_id";
        }
        return $sql;
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

    public function getMediawikiDatabaseName(Project $project, $return_default = true) {
        $project_id = $this->da->escapeInt($project->getID());

        $sql  = "SELECT database_name FROM plugin_mediawiki_database WHERE project_id = $project_id";
        $name = $this->retrieveFirstRow($sql);

        if ($name) {
            return $name['database_name'];
        }

        if ($return_default) {
            //old behaviour
            return str_replace ('-', '_', "plugin_mediawiki_". $project->getUnixName());
        }

        return false;
    }

    public function addDatabase($schema, $project_id) {
        $project_id = $this->da->escapeInt($project_id);
        $schema     = $this->da->quoteSmart($schema);

        $sql = "INSERT INTO plugin_mediawiki_database (project_id, database_name)
                VALUES ($project_id, $schema)";

        return $this->update($sql);
    }

    public function clearPageCacheForSchema($schema) {
        $schema = $this->da->quoteSmartSchema($schema);

        $sql = "DELETE FROM $schema.mwobjectcache";
        return $this->update($sql);
    }

    public function updateDatabaseName($project_id, $db_name) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_mediawiki_database WHERE project_id = $project_id";
        $this->update($sql);

        return $this->addDatabase($db_name, $project_id);
    }

    /**
     * Tries to find an existing schema for a project.
     * Checks using, the ID then the shortname then the list table
     * 
     * @return string | false
     */
    public function findSchemaForExistingProject(Project $project) {
        $dbname_with_id   = str_replace ('-', '_', "plugin_mediawiki_". $project->getID());
        $dbname_with_name = str_replace ('-', '_', "plugin_mediawiki_". $project->getUnixName());

        $dbname_with_id   = $this->da->quoteSmart($dbname_with_id);
        $dbname_with_name = $this->da->quoteSmart($dbname_with_name);


        $sql  = "SELECT SCHEMA_NAME AS 'name' FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = $dbname_with_id";
        $row = $this->retrieveFirstRow($sql);
        if ($row) {
            return $row['name'];
        }

        $sql  = "SELECT SCHEMA_NAME AS 'name' FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = $dbname_with_name";
        $row = $this->retrieveFirstRow($sql);
        if ($row) {
            return $row['name'];
        }

        return $this->getMediawikiDatabaseName($project, false);
    }

    public function getCompatibilityViewUsage($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT enable_compatibility_view FROM plugin_mediawiki_admin_options WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    /**
     *
     * @param int $project_id
     * @param bool $enable_compatibility_view
     * @return boolean true if success
     */
    public function updateCompatibilityViewOption($project_id, $enable_compatibility_view) {
        $project_id = $this->da->escapeInt($project_id);
        $enable_compatibility_view = $this->da->escapeInt($enable_compatibility_view);

        $sql = "INSERT INTO plugin_mediawiki_admin_options (project_id, enable_compatibility_view)
                VALUES ($project_id, $enable_compatibility_view)
                ON DUPLICATE KEY
                    UPDATE enable_compatibility_view = VALUES(enable_compatibility_view)";

        return $this->update($sql);
    }

    public function getAccessControl($project_id, $access) {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart($access);

        $sql = "SELECT ugroup_id
                FROM plugin_mediawiki_access_control
                WHERE project_id = $project_id
                  AND access = $access";

        return $this->retrieve($sql);
    }

    public function saveAccessControl($project_id, $access, array $ugroup_ids) {
        $this->da->startTransaction();

        if (! $this->deleteAllAccessControlForProject($project_id, $access)) {
            $this->da->rollback();
        }

        if (! $this->insertNewAccessControlForProject($project_id, $access, $ugroup_ids)) {
            $this->da->rollback();
        }

        return $this->da->commit();
    }

    private function deleteAllAccessControlForProject($project_id, $access) {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart($access);

        $sql = "DELETE FROM plugin_mediawiki_access_control
                WHERE project_id = $project_id
                  AND access = $access";

        return $this->update($sql);
    }

    private function insertNewAccessControlForProject($project_id, $access, array $ugroup_ids) {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart($access);
        $result     = true;

        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup_id = $this->da->escapeInt($ugroup_id);

            $sql = "INSERT INTO plugin_mediawiki_access_control (project_id, access, ugroup_id)
                    VALUES ($project_id, $access, $ugroup_id)";

            $result = $result && (bool) $this->update($sql);
        }

        return $result;
    }

    public function disableAnonymousRegisteredAuthenticated($project_id) {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id) {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        );
    }

    public function updateAllAnonymousToRegistered() {
        return $this->updateGlobalAccessControl(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedToRegistered() {
        return $this->updateGlobalAccessControl(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateAccessControl($project_id, array $old_ugroup_ids, $new_ugroup_id) {
        $project_id     = $this->da->escapeInt($project_id);
        $old_ugroup_ids = $this->da->escapeIntImplode($old_ugroup_ids);
        $new_ugroup_id  = $this->da->escapeInt($new_ugroup_id);

        $sql = "UPDATE plugin_mediawiki_access_control
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id IN ($old_ugroup_ids)
                  AND project_id = $project_id";

        return $this->update($sql);
    }

    private function updateGlobalAccessControl($old_ugroup_id, $new_ugroup_id) {
        $old_ugroup_id = $this->da->escapeInt($old_ugroup_id);
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);

        $sql = "UPDATE plugin_mediawiki_access_control
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id";

        return $this->update($sql);
    }
}
