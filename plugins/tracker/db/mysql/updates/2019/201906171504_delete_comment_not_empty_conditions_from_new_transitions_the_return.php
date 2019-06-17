<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201906171504_delete_comment_not_empty_conditions_from_new_transitions_the_return extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Remove comment not empty conditions from transitions based on (New artifact)';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'DELETE tracker_workflow_transition_condition_comment_notempty.*
                FROM tracker_workflow_transition_condition_comment_notempty
                INNER JOIN tracker_workflow_transition ON (tracker_workflow_transition_condition_comment_notempty.transition_id = tracker_workflow_transition.transition_id)
                WHERE tracker_workflow_transition.from_id = 0';

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while removing the pre conditions.');
        }
    }
}
