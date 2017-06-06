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

class b201706061300_create_table_svn_last_access extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create table plugin_svn_last_access for SVN plugin';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_svn_last_access (
                  repository_id INT(11) UNSIGNED PRIMARY KEY,
                  commit_date INT(11) UNSIGNED NOT NULL
                )';

        $this->db->createTable('plugin_svn_last_access', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_svn_last_access')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_svn_last_access table is missing');
        }
    }
}
