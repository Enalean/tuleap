<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

class b201908081533_add_burnup_subelements_cache_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add burnup subelements cache table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            CREATE TABLE plugin_agiledashboard_tracker_field_burnup_cache_subelements (
              artifact_id  INT(11) NOT NULL,
              timestamp    INT(11) NOT NULL,
              total_subelements INT(11) NULL,
              closed_subelements  INT(11) NULL,
              UNIQUE KEY time_at_field (artifact_id, timestamp)
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_agiledashboard_tracker_field_burnup_cache_subelements', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_tracker_field_burnup_cache_subelements')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_agiledashboard_tracker_field_burnup_cache_subelements is missing'
            );
        }
    }
}
