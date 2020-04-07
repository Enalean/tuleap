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

class b201708231717_add_table_cross_tracker_report extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add tables to store cross tracker reports";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            DROP TABLE IF EXISTS plugin_tracker_cross_tracker_report;
            CREATE TABLE plugin_tracker_cross_tracker_report (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=InnoDB;";

        $this->db->createTable('plugin_tracker_cross_tracker_report', $sql);

        $sql = "
            DROP TABLE IF EXISTS plugin_tracker_cross_tracker_report_tracker;
            CREATE TABLE plugin_tracker_cross_tracker_report_tracker (
                report_id INT(11) NOT NULL,
                tracker_id INT(11) NOT NULL,
                PRIMARY KEY (report_id, tracker_id),
                INDEX idx_cross_tracker_report_id(report_id)
            ) ENGINE=InnoDB;";

        $this->db->createTable('plugin_tracker_cross_tracker_report_tracker', $sql);
    }

    public function postUp()
    {
        if (
            ! $this->db->tableNameExists('plugin_tracker_cross_tracker_report')
            || ! $this->db->tableNameExists('plugin_tracker_cross_tracker_report_tracker')
        ) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('a table is missing');
        }
    }
}
