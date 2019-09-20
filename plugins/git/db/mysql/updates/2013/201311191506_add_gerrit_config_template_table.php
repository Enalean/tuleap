<?php
/**
 * Copyright (c) Enalean SAS - 2013. All rights reserved
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

class b201311191506_add_gerrit_config_template_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add plugin_git_gerrit_config_template table
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
     * Adding the table
     *
     * @return void
     */
    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_git_gerrit_config_template (
                id INT(11) unsigned NOT NULL auto_increment,
                group_id INT(11) NOT NULL,
                name VARCHAR(255) NOT NULL,
                content TEXT,
                PRIMARY KEY (id),
                INDEX idx_gerrit_config_template_by_project (group_id))';

        $this->db->createTable('plugin_git_gerrit_config_template', $sql);
    }

    /**
     * Verify the table creation
     *
     * @return void
     */
    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_git_gerrit_config_template')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_gerrit_config_template table is missing');
        }
    }
}
