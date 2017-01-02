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

class b201612291507_add_report_config extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Adding report expert query limit in database";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->initQueryLimit();
    }

    private function createTable()
    {
        $sql = "
            DROP TABLE IF EXISTS tracker_report_config;
            CREATE TABLE tracker_report_config (
                query_limit INT(1) NOT NULL DEFAULT 15
            )
        ";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while creating table tracker_report_config.'
            );
        }
    }

    private function initQueryLimit()
    {
        $sql = "
            INSERT INTO tracker_report_config
            VALUES (15)
        ";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while saving tracker_report_config.'
            );
        }
    }
}
