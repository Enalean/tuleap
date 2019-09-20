<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class b201503051029_add_restricted_mirrors extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add a plugin_git_restricted_mirrors and plugin_git_restricted_mirrors_allowed_projects tables.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable(
            'plugin_git_restricted_mirrors',
            'CREATE TABLE IF NOT EXISTS plugin_git_restricted_mirrors (
                mirror_id INT(11) unsigned PRIMARY KEY
            );'
        );

        $this->createTable(
            'plugin_git_restricted_mirrors_allowed_projects',
            'CREATE TABLE IF NOT EXISTS plugin_git_restricted_mirrors_allowed_projects (
                mirror_id INT(11) unsigned NOT NULL,
                project_id INT(11) NOT NULL,
                PRIMARY KEY idx(mirror_id, project_id)
            );'
        );
    }

    private function createTable($name, $sql)
    {
        $this->db->createTable($name, $sql);

        if (! $this->db->tableNameExists($name)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($name . ' table is missing');
        }
    }
}
