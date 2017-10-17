<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201710171318_add_artifact_link_usage_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store the artifact link usage per project";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->insertData();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE plugin_tracker_projects_use_artifactlink_types (
            project_id INT(11) UNSIGNED PRIMARY KEY
        ) ENGINE=InnoDB";

        $this->db->createTable('plugin_tracker_projects_use_artifactlink_types', $sql);

        if (! $this->db->tableNameExists('plugin_tracker_projects_use_artifactlink_types')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('the table plugin_tracker_projects_use_artifactlink_types is missing');
        }
    }

    private function insertData()
    {
        $sql = "INSERT INTO plugin_tracker_projects_use_artifactlink_types (project_id)
                SELECT groups.group_id
                FROM groups
                    INNER JOIN service USING (group_id)
                WHERE groups.status != 'D'
                AND service.short_name = 'plugin_tracker'";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding data into plugin_tracker_projects_use_artifactlink_types'
            );
        }
    }
}
