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

class b201407081125_add_missing_column_in_tour_usage_statistics extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add column to know when the tour is ended";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tour_usage_statistics ADD COLUMN the_end TINYINT(1) NOT NULL";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while altering tour_usage_statistics table.');
        }
    }
}
