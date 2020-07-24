<?php
/**
 * Copyright (c) Enalean SAS 2014. All rights reserved
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

class b201412041555_add_tracker_field_computed_cache_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add table for caching computed values of computed fields';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_field_computed_cache (
                artifact_id INT(11) NOT NULL,
                field_id    INT(11) NOT NULL,
                timestamp   INT(11) NOT NULL,
                value       FLOAT(10,4) NULL,
                UNIQUE KEY time_at_field (artifact_id, field_id, timestamp)
            ) ENGINE=InnoDB";
        $this->db->createTable('tracker_field_computed_cache', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_field_computed_cache')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('table tracker_field_computed_cache not created');
        }
    }
}
