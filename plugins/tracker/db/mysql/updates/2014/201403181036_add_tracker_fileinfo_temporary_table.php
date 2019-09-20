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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class b201403181036_add_tracker_fileinfo_temporary_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add tracker_fileinfo_temporary table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_fileinfo_temporary (
                    fileinfo_id int(11) UNSIGNED NOT NULL,
                    last_modified int(11) NOT NULL,
                    created int(11) NOT NULL,
                    tempname varchar(255) default NULL,
                    INDEX idx_fileinfo_id ( fileinfo_id ),
                    INDEX idx_last_modified( last_modified )
                );";
        $this->db->createTable('tracker_fileinfo_temporary', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_fileinfo_temporary')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_fileinfo_temporary table is missing');
        }
    }
}
