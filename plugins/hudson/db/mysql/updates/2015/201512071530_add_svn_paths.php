<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

class b201512071530_add_svn_paths extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add svn_paths column in plugin_hudson_job
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addColumn();
        $this->populateSVNJobsWithStarOperator();
    }

    private function addColumn()
    {
        $sql = "ALTER TABLE plugin_hudson_job
                ADD COLUMN svn_paths TEXT NOT NULL";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column svn_paths to the table plugin_hudson_job');
        }
    }

    private function populateSVNJobsWithStarOperator()
    {
        $sql = "UPDATE plugin_hudson_job
                SET svn_paths = '*'
                WHERE use_svn_trigger = 1";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating the table plugin_hudson_job');
        }
    }
}
