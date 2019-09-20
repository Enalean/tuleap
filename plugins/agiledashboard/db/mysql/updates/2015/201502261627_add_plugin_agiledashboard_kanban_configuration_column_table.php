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

class b201502261627_add_plugin_agiledashboard_kanban_configuration_column_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add plugin_agiledashboard_kanban_configuration_column table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_configuration_column (
                    kanban_id INT(11) NOT NULL,
                    value_id INT(11) NOT NULL,
                    wip_limit INT(11) UNSIGNED,
                    PRIMARY KEY(kanban_id, value_id)
                )";
        $this->db->createTable('plugin_agiledashboard_kanban_configuration_column', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_kanban_configuration_column')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_agiledashboard_kanban_configuration_column table is missing');
        }
    }
}
