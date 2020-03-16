<?php
/**
 * Copyright (c) Enalean SAS 2014. All rights reserved
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
 * Replace version date column values by timestamp
 */
class b201406041516_add_email_gateway_salt extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Adding salt in database";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->initSalt();
    }

    private function createTable()
    {
        $sql = "
            CREATE TABLE email_gateway_salt (
                salt VARCHAR(255)
            )
        ";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while creating table email_gateway_salt.');
        }
    }

    private function initSalt()
    {
        $salt = hash("sha256", uniqid(rand(), true));

        $sql = "
            INSERT INTO email_gateway_salt (salt)
            VALUES ('$salt')
        ";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while saving email_gateway_salt.');
        }
    }
}
