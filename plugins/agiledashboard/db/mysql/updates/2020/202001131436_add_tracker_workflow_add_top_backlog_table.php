<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

declare(strict_types=1);

class b202001131436_add_tracker_workflow_add_top_backlog_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add plugin_agiledashboard_tracker_workflow_action_add_top_backlog table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            CREATE TABLE plugin_agiledashboard_tracker_workflow_action_add_top_backlog (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                transition_id INT(11) NOT NULL,
                INDEX idx_wf_transition_id(transition_id)
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_agiledashboard_tracker_workflow_action_add_top_backlog', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_tracker_workflow_action_add_top_backlog')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_agiledashboard_tracker_workflow_action_add_top_backlog is missing'
            );
        }
    }
}
