<?php
/**
* Copyright Enalean (c) 2015. All rights reserved.
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

class b201501061426_add_kanban_id extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add kanban id in plugin_agiledashboard_kanban.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_agiledashboard_kanban_configuration
                DROP primary key";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while removing primary key in table plugin_agiledashboard_kanban_configuration: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE plugin_agiledashboard_kanban_configuration
                ADD id INT(11) AUTO_INCREMENT PRIMARY KEY";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column id in table plugin_agiledashboard_kanban_configuration: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE plugin_agiledashboard_kanban_configuration
                MODIFY COLUMN tracker_id INT(11) NOT NULL";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while modifying tracker_id type in table plugin_agiledashboard_kanban_configuration: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
