<?php
/**
 * Copyright (c) Enalean, 2013, 2018. All Rights Reserved.
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

class MediawikiDao extends DataAccessObject
{

    public const DEDICATED_DATABASE_PREFIX = 'plugin_mediawiki_';
    public const DEDICATED_DATABASE_TABLE_PREFIX = 'mw';

    private $database_name = array();
    private $table_prefix = array();

    /**
     * @var string
     */
    private $central_database;

    public function __construct($central_database = null)
    {
        parent::__construct();
        $this->central_database = $central_database;
    }

    public function hasDatabase(Project $project)
    {
        return $this->getMediawikiDatabaseName($project, false) !== false;
    }

    public function getMediawikiPagesNumberOfAProject(Project $project)
    {
        $group_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM " . $this->getTableName($project, 'page');

        $result = $this->retrieve($sql);

        if ($result === false) {
            return ['result' => 0];
        }

        return $result->getRow();
    }

    public function getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate(Project $project, $start_date, $end_date)
    {
        $group_id      = $this->da->escapeInt($project->getID());

        $start_date    = date("YmdHis", strtotime($start_date));
        $end_date      = date("YmdHis", strtotime($end_date));

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM " . $this->getTableName($project, 'page') . "
                WHERE
                    page_touched >= $start_date
                    AND
                    page_touched <= $end_date
               ";

        $result = $this->retrieve($sql);

        if ($result === false) {
            return ['result' => 0];
        }

        return $result->getRow();
    }

    public function getCreatedPagesNumberSinceStartDate(Project $project, $start_date)
    {
        $group_id      = $this->da->escapeInt($project->getID());

        $start_date    = date("YmdHis", strtotime($start_date));

        $sql = "SELECT $group_id AS group_id, COUNT(1) AS result
                FROM " . $this->getTableName($project, 'revision') . "
                WHERE
                    rev_parent_id=0
                    AND
                    rev_timestamp >= $start_date
               ";

        $result = $this->retrieve($sql);

        if ($result === false) {
            return ['result' => 0];
        }

        return $result->getRow();
    }

    public function getMediawikiGroupsForUser(PFUser $user, Project $project)
    {
        $user_name     = $this->da->quoteSmart($this->getMediawikiUserName($user->getUnixName()));

        $sql = "SELECT ug_group
                FROM " . $this->getTableName($project, 'user_groups') . "
                    INNER JOIN " . $this->getTableName($project, 'user') . " ON " . $this->getTableName($project, 'user') . ".user_id = " . $this->getTableName($project, 'user_groups') . ".ug_user
                WHERE user_name = $user_name";

        return $this->retrieve($sql);
    }

    public function removeUser(PFUser $user, Project $project)
    {
        $user_id         = $this->getMediawikiUserId($user, $project);
        $escaped_user_id = $this->da->escapeInt($user_id);

        if (! $user_id) {
            return false;
        }

        $this->removeAllUserGroups($project, $escaped_user_id);

        $sql = "DELETE
                FROM " . $this->getTableName($project, 'user') . "
                WHERE user_id = $escaped_user_id";

        return $this->update($sql);
    }

    private function removeAllUserGroups(Project $project, $escaped_user_id)
    {
        $sql = "DELETE
                FROM " . $this->getTableName($project, 'user_groups') . "
                WHERE ug_user = $escaped_user_id";

        return $this->update($sql);
    }

    public function removeAdminsGroupsForUser(PFUser $user, Project $project)
    {
        $user_id         = $this->getMediawikiUserId($user, $project);
        $escaped_user_id = $this->da->escapeInt($user_id);

        if (! $user_id) {
            return false;
        }

         $sql = "DELETE
                 FROM " . $this->getTableName($project, 'user_groups') . "
                 WHERE ug_user = $escaped_user_id
                   AND ug_group IN ('bureaucrat', 'sysop')";

        return $this->update($sql);
    }

    public function renameUser(Project $project, $old_user_name, $new_user_name)
    {
        $old_user_name = $this->da->quoteSmart($this->getMediawikiUserName($old_user_name));
        $new_user_name = $this->da->quoteSmart($this->getMediawikiUserName($new_user_name));

        $sql = "UPDATE " . $this->getTableName($project, 'user') . "
                SET user_name = $new_user_name
                WHERE user_name = $old_user_name";

        $this->update($sql);

        $sql = "UPDATE " . $this->getTableName($project, 'recentchanges') . "
                SET rc_user_text = $new_user_name
                WHERE rc_user_text = $old_user_name";

        $this->update($sql);

        $sql = "UPDATE " . $this->getTableName($project, 'revision') . "
                SET rev_user_text = $new_user_name
                WHERE rev_user_text = $old_user_name";

        return $this->update($sql);
    }

    private function getMediawikiUserId(PFUser $user, Project $project)
    {
        $user_name     = $this->da->quoteSmart($this->getMediawikiUserName($user->getUnixName()));

        $sql = "SELECT user_id
                FROM " . $this->getTableName($project, 'user') . "
                WHERE user_name = $user_name";

        $data = $this->retrieve($sql)->getRow();

        if (! $data) {
            return false;
        }

        return $data['user_id'];
    }

    public function getMediawikiUserGroupMapping(Project $project)
    {
        $group_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT ugroup_id, mw_group_name
                FROM plugin_mediawiki_ugroup_mapping
                WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function addMediawikiUserGroupMapping(Project $project, $unchecked_mw_group_name, $unchecked_param_ugroup_id)
    {
        $group_id = $this->da->escapeInt($project->getID());
        $ugroup_id = $this->da->escapeInt($unchecked_param_ugroup_id);
        $mw_group_name = $this->da->quoteSmart($unchecked_mw_group_name);

        $sql = "INSERT INTO plugin_mediawiki_ugroup_mapping (group_id, mw_group_name, ugroup_id)
                VALUES ($group_id, $mw_group_name, $ugroup_id)";
        return $this->update($sql);
    }

    public function removeMediawikiUserGroupMapping(Project $project, $unchecked_mw_group_name, $unchecked_ugroup_id)
    {
        $group_id = $this->da->quoteSmart($project->getID());
        $ugroup_id = $this->da->escapeInt($unchecked_ugroup_id);
        $mw_group_name = $this->da->quoteSmart($unchecked_mw_group_name);

        $sql = "DELETE FROM plugin_mediawiki_ugroup_mapping
                WHERE group_id = $group_id AND ugroup_id = $ugroup_id AND mw_group_name = $mw_group_name";
        return $this->update($sql);
    }

    public function getMediawikiGroupsMappedForUGroups(PFUser $user, Project $project)
    {
        $group_id   = $this->da->escapeInt($project->getID());
        $ugroup_ids = $this->da->escapeIntImplode($user->getUgroups($project->getID(), null));

        $sql = "SELECT DISTINCT tuleap_mwgroups.real_name
                FROM plugin_mediawiki_ugroup_mapping AS ugroup_mapping
                    JOIN plugin_mediawiki_tuleap_mwgroups AS tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE ugroup_mapping.group_id = $group_id
                AND ugroup_mapping.ugroup_id IN ($ugroup_ids)";
        return $this->retrieve($sql);
    }

    public function getAllMediawikiGroups($project)
    {
        $group_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT DISTINCT tuleap_mwgroups.real_name
                FROM plugin_mediawiki_ugroup_mapping AS ugroup_mapping
                    JOIN plugin_mediawiki_tuleap_mwgroups AS tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE ugroup_mapping.group_id = $group_id";
        return $this->retrieve($sql);
    }

    public function deleteUserGroup($group_id, $ugroup_id)
    {
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
     * @return bool
     */
    public function resetUserGroups(Project $project)
    {
        $group_id      = $this->da->escapeInt($project->getID());

        $this->update("TRUNCATE TABLE " . $this->getTableName($project, 'user_groups'));
        return $this->feedMediawikiUserGroupsWithTuleapMapping($project, $group_id, 0);
    }

    public function resetUserGroupsForUser(PFUser $user, Project $project)
    {
        $group_id       = $this->da->escapeInt($project->getID());
        $forge_user_id  = $this->da->escapeInt($user->getId());
        $user_name      = $this->da->quoteSmart($this->getMediawikiUserName($user->getUnixName()));

        $this->deleteUserGroupsForUser($project, $user_name);
        $this->feedMediawikiUserGroupsWithTuleapMapping($project, $group_id, $forge_user_id);
    }

    private function deleteUserGroupsForUser(Project $project, $user_name)
    {
        return $this->update("DELETE " . $this->getTableName($project, 'user_groups') . "
                              FROM " . $this->getTableName($project, 'user_groups') . "
                                JOIN " . $this->getTableName($project, 'user') . " ON (" . $this->getTableName($project, 'user') . ".user_id = " . $this->getTableName($project, 'user_groups') . ".ug_user)
                              WHERE " . $this->getTableName($project, 'user') . ".user_name = $user_name");
    }

    private function feedMediawikiUserGroupsWithTuleapMapping(Project $project, $group_id, $forge_user_id)
    {
        $sql = "
            INSERT INTO " . $this->getTableName($project, 'user_groups') . "(ug_user, ug_group)
                  (" . $this->getSQLMediawikiGroupsThatMatchStaticGroups($project, $group_id, $forge_user_id) . ")
            UNION (" . $this->getSQLMediawikiGroupsThatMatchProjectAdmins($project, $group_id, $forge_user_id) . ")
            UNION (" . $this->getSQLMediawikiGroupsThatMatchProjectMembers($project, $group_id, $forge_user_id) . ")
            UNION (" . $this->getSQLMediawikiGroupsThatMatchRegisteredUsers($project, $group_id, $forge_user_id) . ")
            UNION (" . $this->getSQLMediawikiGroupsThatMatchAnonymousUsers($project, $group_id, $forge_user_id) . ")";
        return $this->update($sql);
    }

    private function getSQLMediawikiGroupsThatMatchStaticGroups(Project $project, $group_id, $forge_user_id)
    {
        $mwuser = $this->getTableName($project, 'user');
        $sql = "SELECT $mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $mwuser
                    JOIN user ON (user.user_name = REPLACE($mwuser.user_name, ' ', '_'))
                    JOIN ugroup_user ON (ugroup_user.user_id = user.user_id)
                    JOIN ugroup ON (ugroup.ugroup_id = ugroup_user.ugroup_id AND ugroup.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.ugroup_id = ugroup_user.ugroup_id)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchProjectAdmins(Project $project, $group_id, $forge_user_id)
    {
        $mwuser = $this->getTableName($project, 'user');
        $sql = "SELECT  $mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $mwuser
                    JOIN user ON (user.user_name = REPLACE($mwuser.user_name, ' ', '_'))
                    JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = user_group.group_id AND ugroup_mapping.ugroup_id = 4 AND user_group.admin_flags='A')
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchProjectMembers(Project $project, $group_id, $forge_user_id)
    {
        $mwuser = $this->getTableName($project, 'user');
        $sql = "SELECT $mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $mwuser
                    JOIN user ON (user.user_name = REPLACE($mwuser.user_name, ' ', '_'))
                    JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = user_group.group_id AND ugroup_mapping.ugroup_id = 3)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)";
        if ($forge_user_id != 0) {
            $sql .= " WHERE user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchRegisteredUsers(Project $project, $group_id, $forge_user_id)
    {
        $mwuser = $this->getTableName($project, 'user');
        $sql = "SELECT $mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $mwuser
                    JOIN user ON (user.user_name = REPLACE($mwuser.user_name, ' ', '_'))
                    LEFT JOIN user_group ON (user_group.user_id = user.user_id and user_group.group_id = $group_id)
                    JOIN plugin_mediawiki_ugroup_mapping ugroup_mapping ON (ugroup_mapping.group_id = $group_id AND ugroup_mapping.ugroup_id = 2)
                    JOIN plugin_mediawiki_tuleap_mwgroups tuleap_mwgroups ON (tuleap_mwgroups.mw_group_name = ugroup_mapping.mw_group_name)
                WHERE user_group.user_id IS NULL";
        if ($forge_user_id != 0) {
            $sql .= " AND user.user_id = .$forge_user_id";
        }
        return $sql;
    }

    private function getSQLMediawikiGroupsThatMatchAnonymousUsers(Project $project, $group_id, $forge_user_id)
    {
        $mwuser = $this->getTableName($project, 'user');
        $sql = "SELECT $mwuser.user_id, tuleap_mwgroups.real_name AS ug_name
                FROM $mwuser
                    JOIN user ON (user.user_name = REPLACE($mwuser.user_name, ' ', '_'))
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
    private function getMediawikiUserName($user_name)
    {
        $user_name_with_first_char_uppercase = ucfirst($user_name);

        return str_replace('_', ' ', $user_name_with_first_char_uppercase);
    }

    public function getTableName(Project $project, $table_name)
    {
        return $this->getMediawikiDatabaseName($project) . '.' . $this->getMediawikiTableNamePrefix($project) . $table_name;
    }

    public function getMediawikiTableNamePrefix(Project $project)
    {
        if (! isset($this->table_prefix[$project->getID()])) {
            if ($this->getMediawikiDatabaseName($project) == $this->central_database) {
                $this->table_prefix[$project->getID()] = $this->getTableNamePrefixInCentralDb($project);
            } else {
                $this->table_prefix[$project->getID()] = self::DEDICATED_DATABASE_TABLE_PREFIX;
            }
        }
        return $this->table_prefix[$project->getID()];
    }

    private function getTableNamePrefixInCentralDb(Project $project)
    {
        return 'mw_' . $project->getID() . '_';
    }

    public function getMediawikiDatabaseName(Project $project, $return_default = true)
    {
        if (! isset($this->database_name[$project->getID()])) {
            $project_id = $this->da->escapeInt($project->getID());

            $sql  = "SELECT database_name FROM plugin_mediawiki_database WHERE project_id = $project_id";
            $name = $this->retrieveFirstRow($sql);

            if ($name) {
                $this->database_name[$project->getID()] = $name['database_name'];
            } elseif ($return_default) {
                //old behaviour
                $this->database_name[$project->getID()] = str_replace('-', '_', self::DEDICATED_DATABASE_PREFIX . $project->getUnixName());
            } else {
                $this->database_name[$project->getID()] = false;
            }
        }

        return $this->database_name[$project->getID()];
    }

    public function getDatabaseNameForCreation(Project $project)
    {
        if ($this->central_database) {
            return $this->central_database;
        } else {
            $db_name = self::DEDICATED_DATABASE_PREFIX . $project->getID();
            if ($this->update('CREATE DATABASE ' . $db_name)) {
                return $db_name;
            }
            throw new Exception("Unable to create database $db_name");
        }
    }

    public function getTablePrefixForCreation(Project $project)
    {
        if ($this->central_database) {
            return $this->getTableNamePrefixInCentralDb($project);
        }
        return self::DEDICATED_DATABASE_TABLE_PREFIX;
    }

    public function addDatabase($schema, $project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $schema     = $this->da->quoteSmart($schema);

        $sql = "INSERT INTO plugin_mediawiki_database (project_id, database_name)
                VALUES ($project_id, $schema)";

        return $this->update($sql);
    }

    public function clearPageCacheForProject(Project $project)
    {
        $sql = "DELETE FROM " . $this->getTableName($project, 'objectcache');
        return $this->update($sql);
    }

    public function updateDatabaseName($project_id, $db_name)
    {
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
    public function findSchemaForExistingProject(Project $project)
    {
        if ($this->hasTablesInCentralDatabase($project)) {
            return $this->central_database;
        }

        $dbname_with_id   = str_replace('-', '_', self::DEDICATED_DATABASE_PREFIX . $project->getID());
        $dbname_with_name = str_replace('-', '_', self::DEDICATED_DATABASE_PREFIX . $project->getUnixName());

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

    private function hasTablesInCentralDatabase(Project $project)
    {
        if ($this->central_database) {
            $central_db = $this->da->quoteSmart($this->central_database);
            $prefix     = $this->da->quoteLikeValueSuffix($this->getTableNamePrefixInCentralDb($project));

            $sql = "SELECT 1 FROM
            INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = $central_db
            AND TABLE_NAME LIKE $prefix
            LIMIT 1";

            return $this->retrieveCount($sql) > 0;
        }
        return false;
    }

    public function getCompatibilityViewUsage($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT enable_compatibility_view FROM plugin_mediawiki_admin_options WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    /**
     *
     * @param int $project_id
     * @param 0|1 $enable_compatibility_view
     * @return bool true if success
     */
    public function updateCompatibilityViewOption($project_id, $enable_compatibility_view)
    {
        $project_id = $this->da->escapeInt($project_id);
        $enable_compatibility_view = $this->da->escapeInt($enable_compatibility_view);

        $sql = "INSERT INTO plugin_mediawiki_admin_options (project_id, enable_compatibility_view)
                VALUES ($project_id, $enable_compatibility_view)
                ON DUPLICATE KEY
                    UPDATE enable_compatibility_view = VALUES(enable_compatibility_view)";

        return $this->update($sql);
    }

    public function getAccessControl($project_id, $access)
    {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart($access);

        $sql = "SELECT ugroup_id
                FROM plugin_mediawiki_access_control
                WHERE project_id = $project_id
                  AND access = $access";
        return $this->retrieve($sql);
    }

    public function getAccessControlForProjectContainingUGroup($project_id, $access, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);
        $access     = $this->da->quoteSmart($access);

        $sql = "SELECT acces_control.*
                FROM plugin_mediawiki_access_control      AS ugroup_access_control
                  JOIN plugin_mediawiki_access_control    AS acces_control
                    ON ugroup_access_control.project_id = acces_control.project_id
                    AND ugroup_access_control.access    = acces_control.access
                WHERE ugroup_access_control.project_id = $project_id
                  AND ugroup_access_control.access     = $access
                  AND ugroup_access_control.ugroup_id  = $ugroup_id";

        return $this->retrieve($sql);
    }

    public function saveAccessControl($project_id, $access, array $ugroup_ids)
    {
        $this->da->startTransaction();

        if (! $this->deleteAllAccessControlForProject($project_id, $access)) {
            $this->da->rollback();
        }

        if (! $this->insertNewAccessControlForProject($project_id, $access, $ugroup_ids)) {
            $this->da->rollback();
        }

        return $this->da->commit();
    }

    private function deleteAllAccessControlForProject($project_id, $access)
    {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart($access);

        $sql = "DELETE FROM plugin_mediawiki_access_control
                WHERE project_id = $project_id
                  AND access = $access";

        return $this->update($sql);
    }

    private function insertNewAccessControlForProject($project_id, $access, array $ugroup_ids)
    {
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

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id)
    {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        );
    }

    public function updateAllAnonymousToRegistered()
    {
        return $this->updateGlobalAccessControl(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedToRegistered()
    {
        return $this->updateGlobalAccessControl(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateAccessControl($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $old_ugroup_ids = $this->da->escapeIntImplode($old_ugroup_ids);
        $new_ugroup_id  = $this->da->escapeInt($new_ugroup_id);

        $sql = "UPDATE plugin_mediawiki_access_control
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id IN ($old_ugroup_ids)
                  AND project_id = $project_id";

        return $this->update($sql);
    }

    private function updateGlobalAccessControl($old_ugroup_id, $new_ugroup_id)
    {
        $old_ugroup_id = $this->da->escapeInt($old_ugroup_id);
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);

        $sql = "UPDATE plugin_mediawiki_access_control
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id";

        return $this->update($sql);
    }
}
