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

class b201610041409_create_plugin_git_full_history_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Create table plugin_git_full_history for Git plugin';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_git_full_history (
                  repository_id int(10) unsigned NOT NULL,
                  user_id int(11) NOT NULL,
                  day int(11) NOT NULL,
                  git_browse_operations int(11) NOT NULL default 0,
                  PRIMARY KEY (repository_id, user_id, day)
                )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating the plugin_git_full_history table.');
        }
    }
}
