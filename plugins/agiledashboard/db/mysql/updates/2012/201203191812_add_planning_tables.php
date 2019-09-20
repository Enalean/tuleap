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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201203191812_add_planning_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add tables to store planning
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_agiledashboard_planning (
                  id int(11) NOT NULL auto_increment,
                  name varchar(255) NOT NULL,
                  release_tracker_id int(11) NOT NULL,
                  KEY idx(id, release_tracker_id)
                )";
        $this->db->createTable('plugin_agiledashboard_planning', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_agiledashboard_planning_backlog_tracker (
                  planning_id int(11) NOT NULL,
                  tracker_id int(11) NOT NULL,
                  KEY idx(planning_id, tracker_id)
                )";
        $this->db->createTable('plugin_agiledashboard_planning_backlog_tracker', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_agiledashboard_planning')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_agiledashboard_planning table is missing');
        }

        if (!$this->db->tableNameExists('plugin_agiledashboard_planning_backlog_tracker')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_agiledashboard_planning_backlog_tracker table is missing');
        }
    }
}
