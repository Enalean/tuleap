<?php
/**
 * Copyright (c) Enalean 2016. All rights reserved
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

class b201602021420_create_plugin_svn_accessfile_history_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Create table plugin_svn_accessfile_history for SVN plugin';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_svn_accessfile_history(
                    id INT(11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                    version_number int(11) NOT NULL,
                    repository_id int(11) NOT NULL,
                    content text NOT NULL,
                    version_date int(11) NOT NULL,
                    INDEX repository_idx (repository_id)
        )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating the plugin_svn_accessfile_history table for SVN plugin.');
        }
    }
}
