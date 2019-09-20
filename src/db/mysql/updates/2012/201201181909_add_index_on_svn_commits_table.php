<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

class b201201181909_add_index_on_svn_commits_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add index on repositoryid and date on svn_commits table in order to speed-up
Computation of svn statistics.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->log->warn('Following operations might take a while, please be patient...');
        $sql = "ALTER TABLE svn_commits
                ADD INDEX idx_repositoryid_date (repositoryid, date)";
        $this->db->addIndex('svn_commits', 'idx_repositoryid_date', $sql);
    }

    public function postUp()
    {
        // As of forgeupgrade 1.2 indexNameExists is buggy, so cannot rely on it for post upgrade check
        // Assume it's ok...

        /*if (!$this->db->indexNameExists('svn_commits', 'idx_repositoryid_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Index "idx_repositoryid_date" is missing in "svn_commits"');
            }*/
    }
}
