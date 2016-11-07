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

class b201611031112_add_svn_cache_parameter_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add table svn_cache_parameter to store SVN cache configuration parameter';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->insertDefaultValues();
        $this->refreshSvnrootFile();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS svn_cache_parameter (
                    name VARCHAR(255) PRIMARY KEY,
                    value VARCHAR(255)
                )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding svn_cache_parameter table.'
            );
        }
    }

    private function insertDefaultValues()
    {
        $sql = "INSERT IGNORE INTO svn_cache_parameter VALUES ('maximum_credentials' , '10'), ('lifetime', '5')";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while inserting the default parameters of the SVN cache.'
            );
        }
    }

    private function refreshSvnrootFile()
    {
        $output      = null;
        $return_code = 0;
        exec(
            '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/force_refresh_codendi_svnroot.php',
            $output,
            $return_code
        );
        if ($return_code !== 0) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while updating the SVNRoot file with the new default SVN cache parameters.'
            );
        }
    }
}
