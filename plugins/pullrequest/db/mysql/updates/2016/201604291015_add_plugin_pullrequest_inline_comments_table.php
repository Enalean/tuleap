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

class b201604291015_add_plugin_pullrequest_inline_comments_table extends ForgeUpgrade_Bucket // phpcs:ignore
{

    public function description()
    {
        return <<<EOT
Add plugin_pullrequest_inline_comments table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_pullrequest_inline_comments (
            id              INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            pull_request_id INT(11) NOT NULL,
            user_id         INT(11) NOT NULL,
            post_date       INT(11) NOT NULL,
            file_path       TEXT    NOT NULL,
            unidiff_offset  INT(6)  NOT NULL,
            content         TEXT    NOT NULL,
            is_outdated     BOOL    NOT NULL DEFAULT false
        )";

        $this->db->createTable('plugin_pullrequest_inline_comments', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_pullrequest_inline_comments')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_pullrequest_inline_comments table is missing');
        }
    }
}
