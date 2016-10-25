<?php
/**
* Copyright Enalean (c) 2016. All rights reserved.
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

class b201611030908_replace_start_date_and_duration_columns_by_send_time extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Replace the start date and the duration by a send time.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql1 = "ALTER TABLE plugin_botmattermost_agiledashboard
                CHANGE start_time send_time time;";
        $sql2 = "ALTER TABLE plugin_botmattermost_agiledashboard
                DROP COLUMN duration;";

        if (! $this->db->dbh->exec($sql1)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while column start_date is replaced by send_time in table plugin_botmattermost_agiledashboard: '.implode(', ', $this->db->dbh->errorInfo()));
        }
        if (! $this->db->dbh->exec($sql2)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting column duration in table plugin_botmattermost_agiledashboard: '.implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}