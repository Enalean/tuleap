<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201907171827_populate_timeframe_semantic extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Populate tracker_semantic_timeframe table.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "REPLACE INTO tracker_semantic_timeframe (tracker_id, start_date_field_id, duration_field_id)
            SELECT tracker_id, start_date_field.id, duration_field.id
            FROM tracker_field as start_date_field
                INNER JOIN tracker_field as duration_field USING (tracker_id)
            WHERE start_date_field.name = 'start_date'
              AND start_date_field.formElement_type = 'date'
              AND start_date_field.use_it = 1
              AND duration_field.name = 'duration'
              AND duration_field.formElement_type IN ('computed', 'float', 'int')
              AND duration_field.use_it = 1
        ";

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while populating tracker_semantic_timeframe table');
        }
    }
}
