<?php
/**
* Copyright Enalean (c) 2017. All rights reserved.
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

class b201704110929_add_channel_for_stand_up_summary extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add channels for stand-up summary.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql1 = "CREATE TABLE IF NOT EXISTS `plugin_botmattermost_agiledashboard_notification_channel` (
                    notification_id int(11) NOT NULL ,
                    channel_name VARCHAR(255) NOT NULL ,
                    PRIMARY KEY(notification_id, channel_name)
                )";
        $sql2 = "CREATE TABLE IF NOT EXISTS `plugin_botmattermost_agiledashboard_notification` (
                    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    bot_id int(11) UNSIGNED NOT NULL ,
                    project_id int(11) UNSIGNED NOT NULL UNIQUE ,
                    send_time time NOT NULL
                )";

        $sql3 = "INSERT INTO `plugin_botmattermost_agiledashboard_notification` (bot_id, project_id, send_time)
                 SELECT * FROM plugin_botmattermost_agiledashboard";

        if ($this->db->dbh->exec($sql1) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating plugin_botmattermost_agiledashboard_notification_channel table: '.implode(', ', $this->db->dbh->errorInfo()));
        }
        if ($this->db->dbh->exec($sql2) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating plugin_botmattermost_agiledashboard_notification table: '.implode(', ', $this->db->dbh->errorInfo()));
        }
        if ($this->db->dbh->exec($sql3) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while copying values from plugin_botmattermost_agiledashboard to plugin_botmattermost_agiledashboard_notification: '.implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}