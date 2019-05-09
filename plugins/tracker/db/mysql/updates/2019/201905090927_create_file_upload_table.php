<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class b201905090927_create_file_upload_table extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Create plugin_tracker_file_upload table.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_tracker_file_upload (
            fileinfo_id int(11) PRIMARY KEY,
            expiration_date int(11) UNSIGNED,
            field_id int(11) NOT NULL,
            KEY idx_expiration_date(expiration_date)
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_tracker_file_upload', $sql);
    }
}
