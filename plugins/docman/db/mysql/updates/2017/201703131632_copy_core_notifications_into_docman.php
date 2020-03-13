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

class b201703131632_copy_core_notifications_into_docman extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Copy core notifications into docman plugin";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createPluginTable();
        $this->copyData();
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_docman_notifications')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_docman_notifications table is missing');
        }
    }

    private function createPluginTable()
    {
        $sql = "CREATE TABLE plugin_docman_notifications (
                    item_id int(11) NOT NULL default '0',
                    user_id int(11) NOT NULL default '0',
                    type varchar(100) NOT NULL default '',
                    PRIMARY KEY (item_id, user_id, type)
                )";

        $this->db->createTable('plugin_docman_notifications', $sql);
    }

    private function copyData()
    {
        $sql = "INSERT INTO plugin_docman_notifications (item_id, user_id, type)
                SELECT object_id, user_id, type
                FROM notifications";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while copying table. ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
