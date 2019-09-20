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

class b201408121036_add_artifact_unsubscribe_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add table for unsubscribe option in artifact';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_artifact_unsubscribe (
                    artifact_id int(11) NOT NULL,
                    user_id int(11) NOT NULL,
                    PRIMARY KEY (artifact_id, user_id)
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_artifact_unsubscribe', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_artifact_unsubscribe')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_artifact_unsubscribe');
        }
    }
}
