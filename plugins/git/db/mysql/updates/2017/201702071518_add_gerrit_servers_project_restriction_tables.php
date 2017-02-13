<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201702071518_add_gerrit_servers_project_restriction_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add new project restriction tables for Gerrit servers.";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_restricted_gerrit_servers (
                  gerrit_server_id INT(11) unsigned PRIMARY KEY
               )";

        $this->db->createTable('plugin_git_restricted_gerrit_servers', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_restricted_gerrit_servers_allowed_projects (
                  gerrit_server_id INT(11) unsigned NOT NULL,
                  project_id INT(11) NOT NULL,
                  PRIMARY KEY idx(gerrit_server_id, project_id)
                )";

        $this->db->createTable('plugin_git_restricted_gerrit_servers_allowed_projects', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_restricted_gerrit_servers')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_git_restricted_gerrit_servers table is missing');
        }

        if (! $this->db->tableNameExists('plugin_git_restricted_gerrit_servers_allowed_projects')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_git_restricted_gerrit_servers_allowed_projects table is missing');
        }
    }
}
