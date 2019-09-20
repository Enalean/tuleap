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

class b201305161743_remove_unique_from_svn_checkins extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Remove unique from svn checkins
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE svn_checkins DROP KEY uniq_checkins_idx";
        if ($this->db->tableNameExists('svn_checkins')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while removing key uniq_checkins_idx from table svn_checkins');
            }
        }

        $sql2 = "ALTER TABLE svn_checkins ADD KEY checkins_idx (commitid,dirid,fileid)";
        if ($this->db->tableNameExists('svn_checkins')) {
            $res = $this->db->dbh->exec($sql2);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding non-unique key uniq_checkins_idx to table svn_checkins');
            }
        }
    }

    public function postUp()
    {
    }
}
