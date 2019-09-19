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

class b201407041153_add_tour_usage_statistic_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store usage statistics of tours";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tour_usage_statistics (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            executed_on INT(11) UNSIGNED NOT NULL,
            tour_name VARCHAR(255) NOT NULL,
            nb_steps TINYINT(4) UNSIGNED NOT NULL,
            current_step TINYINT(4) UNSIGNED NOT NULL
        );";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding tour_usage_statistics table.');
        }
    }
}
