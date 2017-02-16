<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201702151712_create_table_to_notify_user extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add table to store the user to notify after a post receive event";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_post_receive_notification_user (
                    repository_id INT(10) UNSIGNED NOT NULL,
                    user_id INT(11) NOT NULL,
                    PRIMARY KEY (repository_id, user_id)
                )";

        $this->db->createTable('plugin_git_post_receive_notification_user', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_post_receive_notification_user')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_git_post_receive_notification_user table is missing');
        }
    }
}
