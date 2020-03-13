<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\Project\UserRemover;

/**
 * Database access to ldap user group
 *
 */
class LDAP_ProjectGroupDao extends DataAccessObject
{
    /**
     * @var UserRemover
     */
    private $user_removal;

    public function __construct(LegacyDataAccessInterface $da, UserRemover $user_removal)
    {
        parent::__construct($da);

        $this->user_removal = $user_removal;
    }

    /**
     * Search one user group by id
     *
     * @param int $groupId Project id
     *
     * @return DataAccessResult
     */
    public function searchByGroupId($groupId)
    {
        $sql = 'SELECT * FROM plugin_ldap_project_group' .
            ' WHERE group_id = ' . db_ei($groupId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return $dar->getRow();
        } else {
            return false;
        }
    }

    /**
     * Associate one Codendi user group to an LDAP group
     *
     * @return bool
     */
    public function linkGroupLdap($project_id, $ldap_dn, $bind, $synchronization)
    {
        $project_id      = $this->da->escapeInt($project_id);
        $ldap_dn         = $this->da->quoteSmart($ldap_dn);
        $synchronization = $this->da->quoteSmart($synchronization);
        $bind            = $this->da->quoteSmart($bind);

        $sql = "INSERT INTO plugin_ldap_project_group (group_id, ldap_group_dn, synchro_policy, bind_option)
                VALUES ($project_id, $ldap_dn, $synchronization, $bind)";

        return $this->update($sql);
    }

    /**
     * Remove link between project members and a LDAP group
     *
     * @param int $groupId Project id
     *
     * @return bool
     */
    public function unlinkGroupLdap($groupId)
    {
        $sql = 'DELETE FROM plugin_ldap_project_group' .
            ' WHERE group_id = ' . db_ei($groupId);
        return $this->update($sql);
    }

    /**
     * Object oriented wrapper for account_add_user_to_group
     *
     * @param int $groupId Project id
     * @param String  $name    User unix name
     *
     * @return bool
     */
    public function addUserToGroup($groupId, $name)
    {
        include_once __DIR__ . '/../../../src/www/include/account.php';
        return account_add_user_to_group($groupId, $name);
    }

    /**
     * Object oriented wrapper for account_remove_user_from_group
     *
     * @param int $project_id Project id
     * @param int $user_id User id
     *
     * @return bool
     */
    public function removeUserFromGroup($project_id, $user_id)
    {
        return $this->user_removal->removeUserFromProject($project_id, $user_id);
    }

    public function isProjectBindingSynchronized($project_id)
    {
        $project_id              = $this->da->escapeInt($project_id);
        $auto_synchronized_value = $this->da->quoteSmart(LDAP_GroupManager::AUTO_SYNCHRONIZATION);

        $sql = "SELECT NULL
                FROM plugin_ldap_project_group
                WHERE group_id = $project_id
                  AND synchro_policy = $auto_synchronized_value
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    public function doesProjectBindingKeepUsers($project_id)
    {
        $project_id       = $this->da->escapeInt($project_id);
        $keep_users_value = $this->da->quoteSmart(LDAP_GroupManager::PRESERVE_MEMBERS_OPTION);

        $sql = "SELECT NULL
                FROM plugin_ldap_project_group
                WHERE group_id = $project_id
                  AND bind_option = $keep_users_value
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    public function getSynchronizedProjects()
    {
        $auto_synchronized_value = $this->da->quoteSmart(LDAP_GroupManager::AUTO_SYNCHRONIZATION);

        $sql = "SELECT *
                FROM plugin_ldap_project_group
                  INNER JOIN groups ON (groups.group_id = plugin_ldap_project_group.group_id)
                WHERE synchro_policy = $auto_synchronized_value
                  AND groups.status IN ('A', 's')";

        return $this->retrieve($sql);
    }
}
