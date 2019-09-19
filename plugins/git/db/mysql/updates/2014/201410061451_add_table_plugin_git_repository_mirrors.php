<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class b201410061451_add_table_plugin_git_repository_mirrors extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add a plugin_git_repository_mirrors table and remove repository_is_mirrored column from the git_plugins table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createPluginGitRepositoryMirrors();
        $this->removeIsMirroredColumn();
    }

    private function createPluginGitRepositoryMirrors()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_git_repository_mirrors (
                    repository_id INT(10) NOT NULL,
                    mirror_id INT(10) NOT NULL,
                    PRIMARY KEY (repository_id, mirror_id)
                );';
        $this->db->createTable('plugin_git_repository_mirrors', $sql);

        if (!$this->db->tableNameExists('plugin_git_repository_mirrors')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_git_repository_mirrors table is missing');
        }
    }

    private function removeIsMirroredColumn()
    {
        $sql = "INSERT INTO plugin_git_repository_mirrors (repository_id, mirror_id) SELECT r.repository_id, m.id  FROM plugin_git r, plugin_git_mirrors m WHERE r.repository_is_mirrored=1";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating plugin_git_repository_mirrors table.');
        }

        $sql = "ALTER TABLE plugin_git DROP COLUMN repository_is_mirrored";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while removing repository_is_mirrored column from plugin_git table.');
        }
    }
}
