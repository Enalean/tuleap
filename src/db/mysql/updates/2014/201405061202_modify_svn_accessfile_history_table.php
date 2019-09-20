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
 * Modify svn accessfile history table
 */
class b201405061202_modify_svn_accessfile_history_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "svn accessfile history table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addIndexonGroupId();
        $this->removeUnusedSha1ContentColumn();
    }

    private function addIndexonGroupId()
    {
        $sql = "ALTER TABLE svn_accessfile_history
                ADD INDEX idx_svn_accessfile_group_id(group_id)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding index svn_accessfile_history table.');
        }
    }

    private function removeUnusedSha1ContentColumn()
    {
        $sql = "ALTER TABLE svn_accessfile_history
                DROP COLUMN sha1_content";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while dropping sha1_content column.');
        }
    }
}
