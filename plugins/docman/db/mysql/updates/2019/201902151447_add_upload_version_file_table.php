<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201902151447_add_upload_version_file_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add a table to manage document version being uploaded';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_docman_new_version_upload(
            id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            expiration_date INT(11) UNSIGNED NOT NULL,
            item_id INT(11) UNSIGNED NOT NULL,
            version_title TEXT NULL,
            changelog TEXT NULL,
            user_id INT(11) UNSIGNED NOT NULL,
            filename TEXT NULL,
            filesize INT(11) UNSIGNED NULL,
            INDEX idx_expiration_date (expiration_date)
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_docman_new_version_upload', $sql);
    }
}
