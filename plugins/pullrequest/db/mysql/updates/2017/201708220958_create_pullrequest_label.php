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

class b201708220958_create_pullrequest_label extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Create plugin_pullrequest_label table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_pullrequest_label (
            pull_request_id INT(11) NOT NULL,
            label_id INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (label_id, pull_request_id)
        )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_pullrequest_label')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_pullrequest_label table is missing');
        }
    }
}
