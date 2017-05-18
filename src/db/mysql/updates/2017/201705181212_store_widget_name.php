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

class b201705181212_store_widget_name extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update dashboards_lines_columns_widgets to reference widget name';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE dashboards_lines_columns_widgets
            DROP INDEX `PRIMARY`,
            CHANGE widget_id id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST,
            CHANGE column_id column_id INT(11) UNSIGNED NOT NULL AFTER id,
            ADD name VARCHAR(255) NOT NULL,
            ADD content_id INT NOT NULL DEFAULT '0' AFTER name,
            ADD INDEX col_idx(column_id)
        ";

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while updating dashboards_lines_columns_widgets'
            );
        }
    }
}
