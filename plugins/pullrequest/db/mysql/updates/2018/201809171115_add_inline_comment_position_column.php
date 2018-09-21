<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201809171115_add_inline_comment_position_column extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return "Add position column to inline comments";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_pullrequest_inline_comments ADD (position VARCHAR(10) NOT NULL DEFAULT 'right');";
        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_pullrequest_inline_comments', 'position')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('position column is missing for plugin_pullrequest_inline_comments table.');
        }
    }
}
