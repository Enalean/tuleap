<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

class b201102081526_add_table_plugin_git_post_receive_mail extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add plugin_git_post_receive_mail table to store emails.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_git_post_receive_mail (' .
                    ' recipient_mail varchar(255) NOT NULL,' .
                    ' repository_id INT(10) NOT NULL,' .
                    ' KEY `repository_id` (`repository_id`)
                    );';
        $this->db->createTable('plugin_git_post_receive_mail', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_git_post_receive_mail')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_post_receive_mail table is missing');
        }
    }
}
