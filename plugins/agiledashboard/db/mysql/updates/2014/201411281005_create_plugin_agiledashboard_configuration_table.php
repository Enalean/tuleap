<?php
/**
* Copyright Enalean (c) 2014. All rights reserved.
*
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class b201411281005_create_plugin_agiledashboard_configuration_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Creating table plugin_agiledashboard_configuration.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_agiledashboard_configuration (
                    project_id INT(11) PRIMARY KEY,
                    scrum TINYINT NOT NULL DEFAULT 1,
                    kanban TINYINT NOT NULL
                )";

        $this->db->createTable('plugin_agiledashboard_configuration', $sql);

        $sql = "INSERT INTO plugin_agiledashboard_configuration (project_id, scrum, kanban)
                SELECT group_id, 1, 1
                FROM service
                    JOIN plugin_agiledashboard_kanban ON group_id = project_id
                WHERE short_name = 'plugin_agiledashboard'
                    AND is_used = 1
                    AND is_active = 1";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding values in table plugin_agiledashboard_configuration: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "DROP TABLE plugin_agiledashboard_kanban";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while dropping table plugin_agiledashboard_kanban: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_configuration')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_agiledashboard_configuration table is missing');
        }

        if ($this->db->tableNameExists('plugin_agiledashboard_kanban')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_agiledashboard_kanban table is still here');
        }
    }
}
