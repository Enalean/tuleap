<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

class b201806281722_add_index_tracker_field_list_bind_static_value extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add index on tracker_field_list_bind_static_value table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (! $this->indexNameExists('tracker_field_list_bind_static_value', 'idx_original_value_id')) {
            $sql = "ALTER TABLE tracker_field_list_bind_static_value ADD INDEX idx_original_value_id (original_value_id, id)";

            if ($this->db->dbh->exec($sql) === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('tracker update failed');
            }
        }
    }

    private function indexNameExists($table_name, $index)
    {
        $sql = 'SHOW INDEX FROM ' . $table_name . ' WHERE Key_name LIKE ' . $this->db->dbh->quote($index);
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }
}
