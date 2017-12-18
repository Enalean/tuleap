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

class b201712180948_add_burnup_cache_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store cache value for burnup table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_agiledashboard_tracker_field_burnup_cache (
                artifact_id  INT(11) NOT NULL,
                timestamp    INT(11) NOT NULL,
                total_effort FLOAT(10,4) NULL,
                team_effort  FLOAT(10,4) NULL,
                UNIQUE KEY time_at_field (artifact_id, timestamp)
            ) ENGINE=InnoDB";
        $this->db->createTable('agiledashboard_tracker_field_burnup_cache', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_tracker_field_burnup_cache')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table agiledashboard_tracker_field_burnup_cache is missing'
            );
        }
    }
}
