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

class b20170731211_add_kanban_widget_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add plugin_agiledashboard_kanban_widget table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_widget (
                  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
                  owner_id int(11) unsigned NOT NULL default '0',
                  owner_type varchar(1) NOT NULL default 'u',
                  title varchar(255) NOT NULL,
                  kanban_id int(11) NOT NULL,
                  KEY (owner_id, owner_type)
                )";
        $this->db->createTable('plugin_agiledashboard_kanban_widget', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_kanban_widget')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'plugin_agiledashboard_kanban_widget table is missing'
            );
        }
    }
}
