<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201206211619_add_artifact_priority extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Add table to store artifact priority
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_artifact_priority(
                    curr_id int(11) NULL,
                    succ_id int(11) NULL,
                    `rank`  int(11) NOT NULL,
                    UNIQUE idx(curr_id, succ_id)
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_artifact_priority', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_artifact_priority')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_artifact_priority table is missing');
        }
    }
}
