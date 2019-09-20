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

class b201411251018_add_agiledashboard_kanban_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Creating table plugin_agiledashboard_kanban.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban (
                    project_id INT(11) PRIMARY KEY
                )";
        $this->db->createTable('plugin_agiledashboard_kanban', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_kanban')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_agiledashboard_kanban table is missing');
        }
    }
}
