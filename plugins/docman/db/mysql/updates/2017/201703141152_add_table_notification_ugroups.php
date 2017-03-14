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

class b201703141152_add_table_notification_ugroups extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add a table to store the ugroup to notify after a document update";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_docman_notification_ugroups (
            item_id   INT(11) NOT NULL default 0,
            ugroup_id INT(11) NOT NULL default 0,
            type varchar(100) NOT NULL default '',
            PRIMARY KEY (item_id, ugroup_id, type)
        );";

        $this->db->createTable('plugin_docman_notification_ugroups', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_docman_notification_ugroups')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_docman_notification_ugroups table is missing');
        }
    }
}
