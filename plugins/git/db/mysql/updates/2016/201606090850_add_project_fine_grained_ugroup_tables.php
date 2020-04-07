<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201606090850_add_project_fine_grained_ugroup_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add defaukt fine-grained permissions tables.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions (
                    id int(11) UNSIGNED PRIMARY KEY auto_increment,
                    project_id int(11) NOT NULL,
                    pattern VARCHAR(255) NOT NULL,
                    INDEX idx_default_fine_grained_permissions(project_id, pattern(15)),
                    UNIQUE default_unique_pattern (project_id, pattern)
                )";

        $this->db->createTable('plugin_git_default_fine_grained_permissions', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_writers (
                    permission_id int(11) UNSIGNED,
                    ugroup_id int(11) NOT NULL,
                    PRIMARY KEY (permission_id, ugroup_id)
                )";

        $this->db->createTable('plugin_git_default_fine_grained_permissions_writers', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_rewinders (
                    permission_id int(11) UNSIGNED,
                    ugroup_id int(11) NOT NULL,
                    PRIMARY KEY (permission_id, ugroup_id)
                )";

        $this->db->createTable('plugin_git_default_fine_grained_permissions_rewinders', $sql);
    }

    public function postUp()
    {
        if (
            ! $this->db->tableNameExists('plugin_git_default_fine_grained_permissions') ||
            ! $this->db->tableNameExists('plugin_git_default_fine_grained_permissions_writers') ||
            ! $this->db->tableNameExists('plugin_git_default_fine_grained_permissions_rewinders')
        ) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'plugin_git_default_fine_grained_permissions' .
                'or plugin_git_default_fine_grained_permissions_writers' .
                ' or plugin_git_default_fine_grained_permissions_rewinders table is missing'
            );
        }
    }
}
