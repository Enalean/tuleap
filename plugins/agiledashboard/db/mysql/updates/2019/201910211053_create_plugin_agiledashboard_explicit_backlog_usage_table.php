<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

class b201910211053_create_plugin_agiledashboard_explicit_backlog_usage_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description(): string
    {
        return "Add explicit backlog usage table";
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = " CREATE TABLE plugin_agiledashboard_planning_explicit_backlog_usage (
               project_id INT(11) NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB
        ";

        $this->db->createTable('plugin_agiledashboard_planning_explicit_backlog_usage', $sql);

        $sql = "CREATE TABLE plugin_agiledashboard_planning_artifacts_explicit_backlog (
               project_id INT(11) NOT NULL,
               artifact_id INT(11) NOT NULL,
               PRIMARY KEY(project_id, artifact_id)
            ) ENGINE=InnoDB
        ";

        $this->db->createTable('plugin_agiledashboard_planning_artifacts_explicit_backlog', $sql);
    }
}
