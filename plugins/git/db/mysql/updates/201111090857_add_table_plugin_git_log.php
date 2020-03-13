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

class b201111090857_add_table_plugin_git_log extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add the table plugin_git_log in order to log git pushes.
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
     * Creation of the table
     *
     * @return void
     */
    public function up()
    {
        $sql = 'CREATE TABLE plugin_git_log (' .
                    ' repository_id INT(10) UNSIGNED NOT NULL,' .
                    ' user_id INT(11) UNSIGNED NULL,' .
                    ' push_date INT(11) NOT NULL,' .
                    ' commits_number INT,' .
                    ' INDEX idx_repository_user(repository_id, user_id))';
        $this->db->createTable('plugin_git_log', $sql);
    }

    /**
     * Verify the table creation
     *
     * @return void
     */
    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_git_log')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_log table is missing');
        }
    }
}
