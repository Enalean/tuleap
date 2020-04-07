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

class b201606061654_add_repository_fine_grained_ugroup_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add repository fine-grained permissions tables.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions_writers (
                    permission_id int(11) UNSIGNED,
                    ugroup_id int(11) NOT NULL,
                    PRIMARY KEY (permission_id, ugroup_id)
                )";

        $this->db->createTable('plugin_git_repository_fine_grained_permissions_writers', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions_rewinders (
                    permission_id int(11) UNSIGNED,
                    ugroup_id int(11) NOT NULL,
                    PRIMARY KEY (permission_id, ugroup_id)
                )";

        $this->db->createTable('plugin_git_repository_fine_grained_permissions_rewinders', $sql);
    }

    public function postUp()
    {
        if (
            ! $this->db->tableNameExists('plugin_git_repository_fine_grained_permissions_writers') ||
            ! $this->db->tableNameExists('plugin_git_repository_fine_grained_permissions_rewinders')
        ) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'plugin_git_repository_fine_grained_permissions_writers' .
                ' or plugin_git_repository_fine_grained_permissions_rewinders table is missing'
            );
        }
    }
}
