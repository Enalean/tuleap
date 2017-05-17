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

class b201705161407_create_tables_dashboards_lines extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create tables dashboards_lines';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE dashboards_lines (
                  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  dashboard_id INT(11) UNSIGNED NOT NULL,
                  dashboard_type VARCHAR(255) NOT NULL,
                  layout VARCHAR(255) NOT NULL,
                  rank INT(11) NOT NULL,
                  INDEX idx(dashboard_id, dashboard_type(3))
                )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('dashboards_lines')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('dashboards_lines table is missing');
        }
    }
}
