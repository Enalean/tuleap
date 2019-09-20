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
 * Add svn accessfile history table with first version for each project
 */
class b201405051118_add_svn_accessfile_history_table_with_first_version extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add svn accessfile history table with first version for each project";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createSvnAccessfileHistoryTable();
        $this->createFirstVersionInSvnAccessfileHistoryTable();
    }

    private function createSvnAccessfileHistoryTable()
    {
        $sql = "CREATE TABLE svn_accessfile_history (
                    id INT(11) AUTO_INCREMENT,
                    version_number INT(11) NOT NULL,
                    group_id INT(11) NOT NULL,
                    content TEXT,
                    sha1_content CHAR(40),
                    version_date INT(11),
                    PRIMARY KEY(id)
        )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while creating svn accessfile history table.');
        }
    }

    private function createFirstVersionInSvnAccessfileHistoryTable()
    {
        $sql = "INSERT INTO svn_accessfile_history (version_number, group_id, content, sha1_content, version_date)
                    SELECT 1, group_id, svn_accessfile, SHA1(svn_accessfile), CURRENT_TIMESTAMP
                    FROM groups";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding version in svn accessfile history table.');
        }
    }
}
