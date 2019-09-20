<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201510281350_add_incomingmail_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add table to store incoming mail for artifact emailgateway";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
    }

    private function createTable()
    {
        $this->exec(
            "CREATE TABLE tracker_changeset_incomingmail(
                changeset_id INT(11) NOT NULL PRIMARY KEY,
                raw_mail TEXT NOT NULL
            ) ENGINE=InnoDB",
            'An error occured while adding tracker_changeset_incomingmail table.'
        );
    }

    private function exec($sql, $error_message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
