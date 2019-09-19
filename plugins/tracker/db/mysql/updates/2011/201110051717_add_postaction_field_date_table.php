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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201110051717_add_postaction_field_date_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add post actions field date table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_field_date (
                  id int(11) NOT NULL auto_increment PRIMARY KEY,
                  transition_id int(11) NOT NULL,
                  field_id int(11) UNSIGNED default NULL,
                  value_type tinyint(2) default NULL,
                  INDEX idx_wf_transition_id( transition_id )
                );";
        $this->db->createTable('tracker_workflow_transition_postactions_field_date', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_workflow_transition_postactions_field_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_workflow_transition_postactions_field_date table is missing');
        }
    }
}
