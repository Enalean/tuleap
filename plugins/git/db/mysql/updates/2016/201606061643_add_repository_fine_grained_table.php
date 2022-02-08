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

class b201606061643_add_repository_fine_grained_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Add repository fine-grained permission table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions (
                    id int(11) UNSIGNED PRIMARY KEY auto_increment,
                    repository_id int(10) unsigned NOT NULL,
                    pattern VARCHAR(255) NOT NULL,
                    INDEX idx_repository_fine_grained_permissions(repository_id, pattern(15))
                )";

        $this->db->createTable('plugin_git_repository_fine_grained_permissions', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_repository_fine_grained_permissions')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'plugin_git_repository_fine_grained_permissions table is missing'
            );
        }
    }
}
