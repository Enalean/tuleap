<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

class b201504241113_update_private_projects extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Re-apply permissions for private projects
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->fixPermissions();
        $this->queueEvent();
    }

    private function fixPermissions()
    {
        $sql = "UPDATE permissions p
                    JOIN plugin_git git ON (p.object_id = CAST(git.repository_id AS CHAR) AND permission_type IN ('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))
                    JOIN groups         ON (git.project_id = groups.group_id)
                SET p.ugroup_id = 3
                WHERE groups.access = 'private'
                AND p.ugroup_id < 3";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating permissions');
        }
    }

    private function queueEvent()
    {
        $sql = 'SET SESSION group_concat_max_len = 134217728';
        $this->db->dbh->exec($sql);

        $sql = "INSERT INTO system_event (type, parameters, priority, status, create_date, owner)
                SELECT 'GIT_PROJECTS_UPDATE', GROUP_CONCAT(group_id SEPARATOR '::'), 1, 'NEW', NOW(), 'app'
                FROM groups
                WHERE access = 'private'
                AND group_id > 100
                AND status = 'A'";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while queuing permissions');
        }
    }
}
