<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

class b201209201812_populate_artifact_priority_for_fresh_install extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Populate artifact priority for fresh install
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->isTrackerArtifactPriorityPopulated()) {
            return;
        }

        if ($this->isTrackerArtifactEmpty()) {
            $sql = "INSERT INTO tracker_artifact_priority(curr_id, succ_id, `rank`) VALUES (NULL, NULL, 0)";
            $this->executeQuery($sql);
            return;
        }

        $this->populateTrackerArtifactPriorityWithArtifacts();
    }

    private function populateTrackerArtifactPriorityWithArtifacts()
    {
        $this->executeQuery("SET @curr_rank = 0");
        $this->executeQuery("SET @succ_rank = 0");
        $sql = "INSERT INTO tracker_artifact_priority (curr_id, succ_id, `rank`)
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
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while populate artifact prioritis: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    private function isTrackerArtifactPriorityPopulated()
    {
        $sql = 'SELECT NULL FROM tracker_artifact_priority LIMIT 1';
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        }
        return false;
    }

    private function isTrackerArtifactEmpty()
    {
        $sql = 'SELECT NULL FROM tracker_artifact LIMIT 1';
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return false;
        }
        return true;
    }
}
