<?php
/**
 * Copyright (c) Enalean 2017. All rights reserved
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

class b201703131434_add_table_global_notification_ugroups extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store the ugroup to notify after an artifact update";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_global_notification_ugroups (
            notification_id INT(11) UNSIGNED NOT NULL,
            ugroup_id INT(11) NOT NULL,
            PRIMARY KEY (notification_id, ugroup_id)
        ) ENGINE=InnoDB;";

        $this->db->createTable('tracker_global_notification_ugroups', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_global_notification_ugroups')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('tracker_global_notification_ugroups table is missing');
        }
    }
}
