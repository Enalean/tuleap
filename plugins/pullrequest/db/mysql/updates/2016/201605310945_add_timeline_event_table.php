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

class b201605310945_add_timeline_event_table extends ForgeUpgrade_Bucket // phpcs:ignore
{

    public function description()
    {
        return <<<EOT
Add timeline_event table to keep track of events made on the pullrequest and show it to the user.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_pullrequest_timeline_event (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            pull_request_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            post_date INT(11) NOT NULL,
            type INT(3) NOT NULL,
            INDEX idx_pr_pull_request_id(pull_request_id)
        );";
        $this->db->createTable('plugin_pullrequest_timeline_event', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_pullrequest_timeline_event')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_pullrequest_timeline_event');
        }
    }
}
