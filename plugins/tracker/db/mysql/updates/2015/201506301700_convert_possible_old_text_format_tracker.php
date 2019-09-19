<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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

class b201506301700_convert_possible_old_text_format_tracker extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Convert field that could contains &lt; and &rt; instead of < and >';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->convertTrackerTable();
        $this->convertTrackerFormElementField();
    }

    private function convertTrackerTable()
    {
        $sql = 'UPDATE tracker SET name = REPLACE(REPLACE(name, "&gt;", ">"), "&lt;", "<"), description = REPLACE(REPLACE(description, "&gt;", ">"), "&lt;", "<")';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while converting data in the tracker table');
        }
    }

    private function convertTrackerFormElementField()
    {
        $sql = 'UPDATE tracker_field SET label = REPLACE(REPLACE(label, "&gt;", ">"), "&lt;", "<")';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while converting data in the tracker_field table');
        }
    }
}
