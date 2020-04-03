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

class b201505061050_rename_to_trafficlights extends ForgeUpgrade_Bucket
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
        $this->createTable();
        $this->feedTable();
        $this->dropTable();
        $this->updateServiceTemplate();
        $this->updateService();
        $this->updatePlugin();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_trafficlights(
            project_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
            campaign_tracker_id INT(11) NOT NULL,
            test_definition_tracker_id INT(11) NOT NULL,
            test_execution_tracker_id INT(11) NOT NULL
        )";
        $this->db->createTable('plugin_trafficlights', $sql);

        if (!$this->db->tableNameExists('plugin_trafficlights')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_trafficlights table is missing');
        }
    }

    private function feedTable()
    {
        $sql = "INSERT INTO plugin_trafficlights(project_id, campaign_tracker_id, test_definition_tracker_id, test_execution_tracker_id)
                SELECT project_id, campaign_tracker_id, test_definition_tracker_id, test_execution_tracker_id
                FROM plugin_testing
               ";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while feeding plugin_trafficlights table ');
        }
    }

    private function dropTable()
    {
        $sql = "DROP TABLE plugin_testing";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting plugin_testing');
        }
    }

    private function updateServiceTemplate()
    {
        $sql = "UPDATE service
                SET label = 'plugin_trafficlights:service_lbl_key',
                    description = 'plugin_trafficlights:service_desc_key',
                    short_name = 'plugin_trafficlights',
                    link = '/plugins/trafficlights/?group_id=\$group_id'
                WHERE group_id = 100
                AND short_name = 'testing'
                 OR short_name = 'plugin_testing'
               ";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating service');
        }
    }

    private function updateService()
    {
        $sql = "UPDATE service
                SET label = 'plugin_trafficlights:service_lbl_key',
                    description = 'plugin_trafficlights:service_desc_key',
                    short_name = 'plugin_trafficlights',
                    link = CONCAT('/plugins/trafficlights/?group_id=', group_id)
                WHERE group_id != 100
                AND short_name = 'testing'
                 OR short_name = 'plugin_testing'
               ";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating service');
        }
    }

    private function updatePlugin()
    {
        $sql = "UPDATE plugin
                SET name = 'trafficlights'
                WHERE name = 'testing'
               ";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating plugin');
        }
    }
}
