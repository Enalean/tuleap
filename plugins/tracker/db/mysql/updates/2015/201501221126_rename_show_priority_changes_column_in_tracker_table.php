<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201501221126_rename_show_priority_changes_column_in_tracker_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Rename show_priority_changes to log_priority_changes column in tracker table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tracker CHANGE show_priority_changes log_priority_changes TINYINT(1) NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while renaming column show_priority_changes in tracker table: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('tracker', 'log_priority_changes')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while renaming show_priority_changes column in tracker table');
        }
    }
}
