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

class b20181218_add_plugin_docman_new_document_upload_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add a table to manage document being uploaded';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_docman_new_document_upload (
                    id INT(11) PRIMARY KEY AUTO_INCREMENT,
                    expiration_date INT(11) UNSIGNED NOT NULL,
                    parent_id INT(11) UNSIGNED NOT NULL,
                    title TEXT NULL,
                    description TEXT NULL,
                    user_id INT(11) UNSIGNED NOT NULL,
                    filename TEXT NULL,
                    filesize INT(11) UNSIGNED NULL,
                    INDEX idx_parentid (parent_id),
                    INDEX idx_expiration_date (expiration_date)
                )';

        $this->db->createTable('plugin_docman_new_document_upload', $sql);
    }
}
