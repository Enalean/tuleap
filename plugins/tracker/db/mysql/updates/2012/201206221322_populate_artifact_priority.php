<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201206221322_populate_artifact_priority extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Populate artifact priority
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->executeQuery("SET @curr_rank = 0");
        $this->executeQuery("SET @succ_rank = 0");
        $sql = "INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank)
                SELECT curr.id, succ.id, curr.rank - 1 AS rank
                FROM (
                        (SELECT NULL AS id, @curr_rank := @curr_rank + 1 AS rank)
                        UNION
                        (SELECT id, @curr_rank := @curr_rank + 1 AS rank
                         FROM tracker_artifact
                         ORDER BY id
                        )
                    ) AS curr
                    INNER JOIN
                    (
                        (SELECT id, @succ_rank := @succ_rank + 1 AS rank
                         FROM tracker_artifact
                         ORDER BY id
                        )
                        UNION
                        (SELECT NULL AS id, @succ_rank := @succ_rank + 1 AS rank)
                    ) AS succ
                    USING (rank)";
        $this->executeQuery($sql);
    }

    private function executeQuery($sql)
    {
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populate artifact prioritis: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
