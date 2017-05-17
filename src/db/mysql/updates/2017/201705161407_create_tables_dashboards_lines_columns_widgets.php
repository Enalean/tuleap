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

class b201705161407_create_tables_dashboards_lines_columns_widgets extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create tables dashboards_lines_columns_widgets';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE dashboards_lines_columns_widgets (
                  column_id INT(11) UNSIGNED NOT NULL,
                  widget_id INT(11) UNSIGNED NOT NULL,
                  rank INT(11) NOT NULL,
                  PRIMARY KEY (column_id, widget_id)
                )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('dashboards_lines_columns_widgets')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('dashboards_lines_columns_widgets table is missing');
        }
    }
}
