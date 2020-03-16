<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201607281200_migrate_existing_permissions extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Migrate existings permissions for frs';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $group_frs_admin       = 'FRS_Admin';
        $permission_frs_admin  = 'FRS_ADMIN';
        $frs_admin_description = 'FRS Admin';

        $this->db->dbh->beginTransaction();

        $last_ugroup_id = $this->getLastUGroupId();

        $sql = "INSERT INTO ugroup (name, description, group_id)
                    SELECT DISTINCT '$group_frs_admin', '$frs_admin_description', groups.group_id
                    FROM groups
                        INNER JOIN user_group ON user_group.group_id = groups.group_id
                    WHERE status <> 'D'
                      AND groups.group_id <> 100
                      AND file_flags = 2";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occured while migrating admin frs permissions for ugroup' . $sql);
        }

        $sql = "INSERT INTO ugroup_user (ugroup_id, user_id)
                    SELECT
                        ugroup.ugroup_id,
                        user_id
                    FROM ugroup
                      INNER JOIN user_group ON ugroup.group_id = user_group.group_id
                    WHERE file_flags = 2
                      AND name = '$group_frs_admin'
                      AND ugroup_id > $last_ugroup_id";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occured while migrating admin frs permissions for ugroup_user' . $sql);
        }

        $sql = "INSERT INTO frs_global_permissions (project_id, permission_type, ugroup_id)
                SELECT group_id, '$permission_frs_admin', ugroup_id
                FROM ugroup
                WHERE name = '$group_frs_admin'
                  AND ugroup_id > $last_ugroup_id";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occured while add admin frs permissions in new table' . $sql);
        }

        $this->db->dbh->commit();
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }

    private function getLastUGroupId()
    {
        $sql = "SELECT MAX(ugroup_id) AS last_ugroup_id FROM ugroup";

        $res = $this->db->dbh->query($sql)->fetch();

        return $res['last_ugroup_id'];
    }
}
