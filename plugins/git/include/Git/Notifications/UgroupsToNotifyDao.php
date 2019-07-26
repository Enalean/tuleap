<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use ParagonIE\EasyDB\EasyStatement;
use ProjectUGroup;
use Tuleap\DB\DataAccessObject;

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByRepositoryId($repository_id)
    {
        $sql = 'SELECT ugroup.*
                FROM plugin_git_post_receive_notification_ugroup AS notif
                    INNER JOIN ugroup
                    ON (
                        ugroup.ugroup_id = notif.ugroup_id
                        AND notif.repository_id = ?
                    )';

        return $this->getDB()->run($sql, $repository_id);
    }

    public function delete($repository_id, $ugroup_id)
    {
        $sql = 'DELETE
                FROM plugin_git_post_receive_notification_ugroup
                WHERE repository_id = ?
                  AND ugroup_id = ?';

        try {
            $this->getDB()->run($sql, $repository_id, $ugroup_id);
        } catch (\PDOException $ex) {
            return false;
        }

        return true;
    }

    public function deleteByUgroupId($project_id, $ugroup_id)
    {
        $sql = 'DELETE notif.*
                FROM plugin_git AS repo
                    INNER JOIN plugin_git_post_receive_notification_ugroup AS notif
                    ON (
                      repo.repository_id = notif.repository_id
                      AND repo.project_id = ?
                      AND notif.ugroup_id = ?
                    )';

        $this->getDB()->run($sql, $project_id, $ugroup_id);
    }

    public function deleteByRepositoryId($repository_id)
    {
        $sql = 'DELETE
                FROM plugin_git_post_receive_notification_ugroup
                WHERE repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        $this->updateNotificationUgroups(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id)
    {
        $this->updateNotificationUgroups(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        );
    }

    private function updateNotificationUgroups($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $this->getDB()->beginTransaction();

        try {
            $old_ugroup_ids_in_condition = EasyStatement::open()->in('?*', $old_ugroup_ids);

            $sql = "UPDATE IGNORE plugin_git_post_receive_notification_ugroup AS notif
                  INNER JOIN plugin_git AS git USING (repository_id)
                SET notif.ugroup_id = ?
                WHERE notif.ugroup_id IN ($old_ugroup_ids_in_condition)
                  AND git.project_id = ?
                ";

            $params_update   = [$new_ugroup_id];
            $params_update   = array_merge($params_update, $old_ugroup_ids_in_condition->values());
            $params_update[] = $project_id;
            $this->getDB()->safeQuery($sql, $params_update);

            $sql = "DELETE notif.*
                FROM plugin_git_post_receive_notification_ugroup AS notif
                  INNER JOIN plugin_git AS git USING (repository_id)
                WHERE notif.ugroup_id IN ($old_ugroup_ids_in_condition)
                  AND git.project_id = ?";

            $params_delete   = $old_ugroup_ids_in_condition->values();
            $params_delete[] = $project_id;
            $this->getDB()->safeQuery($sql, $params_delete);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
        }
    }

    public function updateAllAnonymousAccessToRegistered()
    {
        $this->updateAllPermissions(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        $this->updateAllPermissions(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateAllPermissions($old_ugroup_id, $new_ugroup_id)
    {
        $sql = 'UPDATE plugin_git_post_receive_notification_ugroup
                SET ugroup_id = ?
                WHERE ugroup_id = ?';

        $this->getDB()->run($sql, $old_ugroup_id, $new_ugroup_id);
    }

    public function insert($repository_id, $ugroup_id)
    {
        $sql = 'REPLACE INTO plugin_git_post_receive_notification_ugroup(repository_id, ugroup_id)
                VALUES (?, ?)';

        try {
            $this->getDB()->run($sql, $repository_id, $ugroup_id);
        } catch (\PDOException $ex) {
            return false;
        }

        return true;
    }
}
