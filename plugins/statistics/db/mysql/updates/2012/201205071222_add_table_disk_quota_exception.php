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

class b201205071222_add_table_disk_quota_exception extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add table to store disk quota exception requests
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_statistics_disk_quota_exception (
                   group_id int(11) NOT NULL,
                   requester_id int(11) NOT NULL default '0',
                   requested_size int(11) NOT NULL,
                   exception_motivation text,
                   request_date int(11) unsigned NOT NULL default '0',
                   PRIMARY KEY (group_id)
                );";
        $this->db->createTable('plugin_statistics_disk_quota_exception', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_statistics_disk_quota_exception')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_statistics_disk_quota_exception table is missing');
        }
    }
}
