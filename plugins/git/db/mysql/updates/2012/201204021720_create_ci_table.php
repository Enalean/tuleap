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

class b201204021720_create_ci_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add the table plugin_git_ci in order to trigger ci jobs after git pushes.
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
        $sql = 'CREATE TABLE plugin_git_ci (
                job_id INT(11) UNSIGNED NOT NULL,
                repository_id INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (job_id))';
        $this->db->createTable('plugin_git_ci', $sql);
    }

    /**
     * Verify the table creation
     *
     * @return void
     */
    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_git_ci')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_ci table is missing');
        }
    }
}
