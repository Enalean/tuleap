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

class b201503061743_add_homepage_headline extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store homepage headline";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->populateTable();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE homepage_headline (
            language_id VARCHAR(17) NOT NULL PRIMARY KEY,
            headline TEXT NOT NULL
        )";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding homepage_headline table.');
        }
    }

    private function populateTable()
    {
        $sql = "INSERT INTO homepage_headline (language_id, headline) VALUES
            ('en_US', 'Tuleap helps teams to deliver awesome applications, better, faster and easier.
It enables you to plan, track, code and collaborate on software projects. '),
            ('fr_FR', 'Avec Tuleap, les équipes livrent ses applications plus rapidement, plus efficacement et de meilleure qualité.
Venez planifier, suivre, développer & collaborer sur vos projets logiciels.');";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating homepage_headline table.');
        }
    }
}
