<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class b201303200939_add_per_tracker_artifact_id extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'add per tracker artifact id.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE `tracker_artifact`
					ADD COLUMN `per_tracker_artifact_id` INT(11) NOT NULL;";
        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }

        $sql = "
UPDATE tracker_artifact ta
INNER JOIN
(
    SELECT  @row_num := IF(@prev_value=t.tracker_id,@row_num+1,1) AS RowNumber
           ,t.tracker_id
           ,t.id
           ,@prev_value := t.tracker_id
    FROM tracker_artifact t,
           (SELECT @row_num := 1) x,
           (SELECT @prev_value := '') y
    ORDER BY t.tracker_id, t.id ASC
) numbered_ids on (numbered_ids.id = ta.id)
SET per_tracker_artifact_id = numbered_ids.RowNumber;";

         $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
