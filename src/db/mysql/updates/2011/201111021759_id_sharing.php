<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class b201111021759_id_sharing extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add tables to store shared ids between trackers v3 and v5
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $aid = 0;
        $tid = 0;

        $sql = "SELECT IFNULL(MAX(artifact_id), 0) AS last_artifact_id FROM artifact";
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while fetching the last artifact(v3) id: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $row = $res->fetch();
        $aid = $row['last_artifact_id'];
        $res->closeCursor();
        unset($res);

        $sql = "SELECT IFNULL(MAX(group_artifact_id), 0) AS last_tracker_id FROM artifact_group_list";
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while fetching the last tracker(v3) id: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $row = $res->fetch();
        $tid = $row['last_tracker_id'];
        $res->closeCursor();
        unset($res);

        // Is plugin tracker installed?
        if ($this->db->tableNameExists('tracker_artifact')) {
            $sql = "SELECT IFNULL(MAX(id), 0) AS last_artifact_id FROM tracker_artifact";
            $res = $this->db->dbh->query($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while fetching the last artifact(v5) id: ' . implode(', ', $this->db->dbh->errorInfo()));
            }
            $row = $res->fetch();
            $aid = max($aid, $row['last_artifact_id']);
            $res->closeCursor();
            unset($res);

            $sql = "SELECT IFNULL(MAX(id), 0) AS last_tracker_id FROM tracker";
            $res = $this->db->dbh->query($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while fetching the last tracker(v5) id: ' . implode(', ', $this->db->dbh->errorInfo()));
            }
            $row = $res->fetch();
            $tid = max($tid, $row['last_tracker_id']);
            $res->closeCursor();
            unset($res);
        }

        $aid++;
        $tid++;

        $sql = "CREATE TABLE IF NOT EXISTS tracker_idsharing_artifact( id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ) AUTO_INCREMENT = $aid";
        $this->db->createTable('tracker_idsharing_artifact', $sql);
        $sql = "CREATE TABLE IF NOT EXISTS tracker_idsharing_tracker( id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ) AUTO_INCREMENT = $tid";
        $this->db->createTable('tracker_idsharing_tracker', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_idsharing_tracker')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_idsharing_tracker table is missing');
        }
        if (!$this->db->tableNameExists('tracker_idsharing_artifact')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_idsharing_artifact table is missing');
        }
    }
}
