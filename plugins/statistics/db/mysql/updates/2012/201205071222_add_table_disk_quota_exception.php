<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201205071222_add_table_disk_quota_exception extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add table to store disk quota exception requests
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS disk_quota_exception (
                   request_id int(11) unsigned NOT NULL AUTO_INCREMENT,
                   group_id int(11) NOT NULL default '0',
                   requester_id int(11) NOT NULL default '0',
                   requested_size int(11) NOT NULL,
                   exception_motivation text NOT NULL default '',
                   request_status varchar(255) NOT NULL,
                   request_date int(11) unsigned NOT NULL default '0',
                   PRIMARY KEY (request_id)
                );";
        $this->db->createTable('disk_quota_exception', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('disk_quota_exception')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('disk_quota_exception table is missing');
        }
    }

}
?>