<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201612091720_use_only_rank_for_artifact_priority extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Use only rank for artifact priority (may take some time to execute)';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->populateTable();
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_artifact_priority_rank')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Table tracker_artifact_priority_rank not created');
        }
    }

    private function createTable()
    {
        $sql = "CREATE TABLE tracker_artifact_priority_rank(
                    artifact_id INT(11) NULL PRIMARY KEY,
                    rank INT(11) UNSIGNED NOT NULL
                ) ENGINE=InnoDB";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while creating tracker_artifact_priority_rank: ' . implode(
                ', ',
                $this->db->dbh->errorInfo()
            ));
        }
    }

    private function populateTable()
    {
        $sql = "INSERT INTO tracker_artifact_priority_rank(artifact_id, rank)
                SELECT curr_id, rank
                FROM tracker_artifact_priority
                WHERE curr_id IS NOT NULL";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating tracker_artifact_priority_rank: ' . implode(
                ', ',
                $this->db->dbh->errorInfo()
            ));
        }
    }
}
