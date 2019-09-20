<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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

class b201512171700_add_svn_token_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add table svn_token to store SVN authentification token';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS svn_token (
                  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                  user_id INT(11) NOT NULL,
                  token VARCHAR(255) NOT NULL,
                  generated_date INT(11) UNSIGNED NOT NULL,
                  last_usage INT(11) UNSIGNED,
                  last_ip VARCHAR(45),
                  comment TEXT,
                  INDEX idx_user_id (user_id)
                )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding svn_token_table table.');
        }
    }
}
