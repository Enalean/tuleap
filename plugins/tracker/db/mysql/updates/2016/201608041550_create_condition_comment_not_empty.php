<?php
/**
 * Copyright (c) Enalean SAS 2016. All rights reserved
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

class b201608041550_create_condition_comment_not_empty extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Create table tracker_workflow_transition_condition_comment_notempty';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE  tracker_workflow_transition_condition_comment_notempty(
                    transition_id INT(11) NOT NULL PRIMARY KEY,
                    is_comment_required TINYINT(1) NOT NULL
                ) ENGINE=InnoDB";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while creating tracker_workflow_transition_condition_comment_notempty: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_workflow_transition_condition_comment_notempty')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Table tracker_workflow_transition_condition_comment_notempty not created');
        }
    }
}
