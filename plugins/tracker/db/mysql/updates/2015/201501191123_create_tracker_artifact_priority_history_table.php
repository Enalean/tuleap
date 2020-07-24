<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201501191123_create_tracker_artifact_priority_history_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add tracker_artifact_priority_history table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_artifact_priority_history(
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    artifact_id_higher INT(11) NULL,
                    artifact_id_lower INT(11) NULL,
                    prioritized_by INT(11) NOT NULL,
                    prioritized_on INT(11) NOT NULL
                ) ENGINE=InnoDB;";

        $this->db->createTable('tracker_artifact_priority_history', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_artifact_priority_history')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('table tracker_artifact_priority_history not created');
        }
    }
}
