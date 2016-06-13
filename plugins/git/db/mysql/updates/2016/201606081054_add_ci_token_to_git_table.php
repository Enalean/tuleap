<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b20160608_add_ci_token_to_git_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add CI token to git table to restrict access to the CI status update.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the column
     *
     * @return void
     */
    public function up() {
        $sql = 'ALTER TABLE plugin_git ADD ci_token TEXT NULL';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column ci_token to the table plugin_git');
        }
    }

    /**
     * Verify the column creation
     *
     * @return void
     */
    public function postUp() {
        if (!$this->db->columnNameExists('plugin_git', 'ci_token')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column ci_token in table plugin_git is missing');
        }
    }
}
