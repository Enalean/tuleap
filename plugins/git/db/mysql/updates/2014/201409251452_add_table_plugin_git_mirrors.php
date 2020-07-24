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

class b201409251452_add_table_plugin_git_mirrors extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add plugin_git_mirrors table to store mirrors.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_mirrors (
            id INT(11) unsigned NOT NULL auto_increment,
            url VARCHAR(255) NOT NULL,
            ssh_key TEXT NOT NULL,
            PRIMARY KEY (id))";

        $this->db->createTable('plugin_git_mirrors', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_mirrors')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_mirrors table is missing');
        }
    }
}
