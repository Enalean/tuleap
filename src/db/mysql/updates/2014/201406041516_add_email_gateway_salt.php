<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All rights reserved
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
class b201406041516_add_email_gateway_salt extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Adding salt in database';
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
        $sql = '
            CREATE TABLE email_gateway_salt (
                salt VARCHAR(255)
            )
        ';

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while creating table email_gateway_salt.');
        }
    }

    private function initSalt()
    {
        $salt = bin2hex(random_bytes(32));

        $sql = "
            INSERT INTO email_gateway_salt (salt)
            VALUES ('$salt')
        ";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while saving email_gateway_salt.');
        }
    }
}
