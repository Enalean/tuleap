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

class b201806111045_add_status_to_pr_ref_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add status column to the plugin_pullrequest_git_reference table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE plugin_pullrequest_git_reference ADD COLUMN status INT(11) NOT NULL DEFAULT 0';
        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to add the column status to the plugin_pullrequest_git_reference table'
            );
        }

        $sql = 'ALTER TABLE plugin_pullrequest_git_reference ALTER COLUMN status DROP DEFAULT';
        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to remove the default value of the column status in the plugin_pullrequest_git_reference table'
            );
        }
    }
}
