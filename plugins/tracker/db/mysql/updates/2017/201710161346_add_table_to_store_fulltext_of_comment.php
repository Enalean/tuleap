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

class b201710161346_add_table_to_store_fulltext_of_comment extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store the follow-up comment of artifact in fulltext";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_changeset_comment_fulltext(
            comment_id INT(11) NOT NULL PRIMARY KEY,
            stripped_body TEXT DEFAULT NULL,
            FULLTEXT stripped_body_idx(stripped_body)
        ) ENGINE=MyISAM";

        $this->db->createTable('tracker_changeset_comment_fulltext', $sql);
    }

    public function postUp()
    {
        if (
            ! $this->db->tableNameExists('plugin_tracker_cross_tracker_report')
            || ! $this->db->tableNameExists('tracker_changeset_comment_fulltext')
        ) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('a table is missing');
        }
    }
}
