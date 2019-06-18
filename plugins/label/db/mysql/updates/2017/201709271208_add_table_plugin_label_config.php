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
class b201709271208_add_table_plugin_label_config extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Adding table plugin_label_widget_config';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_label_widget (
                  content_id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY
                )';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while creating the table plugin_label_widget'
            );
        }

        $sql = 'CREATE TABLE IF NOT EXISTS plugin_label_widget_config (
                    content_id INT(11) UNSIGNED NOT NULL,
                    label_id INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (content_id, label_id)
        )';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while creating the table plugin_label_widget_config'
            );
        }
    }
}
