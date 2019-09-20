<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201112160838_delete_user_id extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Delete the column user_id in plugin_git.
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
     * Creation of the column
     *
     * @return void
     */
    public function up()
    {
        $sql = "UPDATE plugin_git SET repository_scope = 'I' WHERE user_id IS NOT NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating the column repository_scope to the table plugin_git');
        }

        $sql = 'ALTER TABLE plugin_git 
                    DROP user_id';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while removing the column user_id to the table plugin_git');
        }
    }

    /**
     * Verify the column creation
     *
     * @return void
     */
    public function postUp()
    {
        if ($this->db->columnNameExists('plugin_git', 'user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column user_id in table plugin_git is still existing');
        }
    }
}
