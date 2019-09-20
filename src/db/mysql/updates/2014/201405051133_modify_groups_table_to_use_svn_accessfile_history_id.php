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
 * Modify groups table to use svn accessfile history id
 */
class b201405051133_modify_groups_table_to_use_svn_accessfile_history_id extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Modify groups table to use svn accessfile history id";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->modifySvnAccessfileGroups();
        $this->addNewSvnAccessfileGroupsValue();
    }

    private function modifySvnAccessfileGroups()
    {
        $sql = "ALTER TABLE groups
                    MODIFY svn_accessfile INT(11)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while altering groups table.');
        }
    }

    private function addNewSvnAccessfileGroupsValue()
    {
        $sql = "UPDATE groups, svn_accessfile_history
                SET groups.svn_accessfile = svn_accessfile_history.id
                WHERE svn_accessfile_history.group_id = groups.group_id";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while setting groups svn_access file value.');
        }
    }
}
