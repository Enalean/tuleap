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

class b201901161121_create_postaction_readonly_tables extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Create plugin_tracker_workflow_transition_postactions_read_only and plugin_tracker_workflow_transition_postactions_read_only_fields tables.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createFieldTable();
        $this->createPostActionTable();
    }

    private function createPostActionTable()
    {
        $sql = 'CREATE TABLE plugin_tracker_workflow_transition_postactions_read_only (
            id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            transition_id INT(11) NOT NULL,
            INDEX idx_wf_transition_id( transition_id )
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_tracker_workflow_transition_postactions_read_only', $sql);
    }

    private function createFieldTable()
    {
        $sql = 'CREATE TABLE plugin_tracker_workflow_transition_postactions_read_only_fields (
            postaction_id INT(11) UNSIGNED NOT NULL,
            field_id INT(11) NOT NULL,
            PRIMARY KEY (postaction_id, field_id)
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_tracker_workflow_transition_postactions_read_only_fields', $sql);
    }
}
