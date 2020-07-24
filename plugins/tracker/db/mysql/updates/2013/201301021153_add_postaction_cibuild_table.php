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

class b201301021153_add_postaction_cibuild_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add post actions ci build table.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_cibuild (
                    id int(11) UNSIGNED NOT NULL auto_increment  PRIMARY KEY,
                    transition_id int(11) NOT NULL,
                    job_url varchar(255) default NULL,
                    
                    INDEX idx_wf_transition_id( transition_id )
                );";
        $this->db->createTable('tracker_workflow_transition_postactions_cibuild', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_workflow_transition_postactions_cibuild')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_workflow_transition_postactions_cibuild table is missing');
        }
    }
}
