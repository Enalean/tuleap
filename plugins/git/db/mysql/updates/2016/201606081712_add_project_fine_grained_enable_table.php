<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class b201606081712_add_project_fine_grained_enable_table extends \Tuleap\ForgeUpgrade\Bucket
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
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_enabled (
                    project_id int(11) NOT NULL PRIMARY KEY
                )";

        $this->db->createTable('plugin_git_default_fine_grained_permissions_enabled', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_default_fine_grained_permissions_enabled')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'plugin_git_default_fine_grained_permissions_enabled table is missing'
            );
        }
    }
}
