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

class b201201251529_update_push_date extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String;
     */
    public function description()
    {
        return <<<EOT
Replace the column push_date in the table plugin_git_log which type is date by another one with type int(11)
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Update the column type
     *
     * @return void
     */
    public function up()
    {
        $sql = "ALTER TABLE plugin_git_log DROP COLUMN push_date";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while dropping the column push_date from the table plugin_git_log');
        }
        $sql = "ALTER TABLE plugin_git_log ADD COLUMN push_date INT(11) DEFAULT 0 AFTER user_id";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column push_date to the table plugin_git_log');
        }
    }

    /**
     * Verify the column type
     *
     * @return void
     */
    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_git_log', 'push_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Type of the column push_date in table plugin_git_log still not updated');
        }
    }
}
