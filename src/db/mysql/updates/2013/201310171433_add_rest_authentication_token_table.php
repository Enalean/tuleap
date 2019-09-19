<?php
/**
 * Copyright (c) Enalean SAS 2013. All rights reserved
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

/**
 * Add rest_authentication_token table
 */
class b201310171433_add_rest_authentication_token_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
add rest_authentication_token table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS rest_authentication_token (
                    token VARCHAR(255) NOT NULL,
                    user_id INT(11) NOT NULL,
                    created_on INT(11) NOT NULL,
                    INDEX idx_rest_authentication_token_token (token(10)),
                    INDEX idx_rest_authentication_token_userid (user_id)
                )";

        if (! $this->db->tableNameExists('rest_authentication_token')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding table rest_authentication_token');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('rest_authentication_token')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Table rest_authentication_token not created');
        }
    }
}
