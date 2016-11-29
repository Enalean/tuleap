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
class b201610171725_create_plugin_git_default_fine_grained_regexp extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add default regexp fine-grained permission table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_regexp_enabled (
                    project_id int(11) unsigned NOT NULL PRIMARY KEY
                )";

        $this->db->createTable('plugin_git_default_fine_grained_regexp_enabled', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_default_fine_grained_regexp_enabled')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'plugin_git_default_fine_grained_regexp_enabled table is missing'
            );
        }
    }
}
