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

class b20181221_share_docman_item_id_between_tables extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Share document item ID between tables';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dropTable('plugin_docman_new_document_upload');
        $this->db->dropTable('plugin_docman_item_id');

        $this->db->createTable(
            'plugin_docman_item_id',
            'CREATE TABLE plugin_docman_item_id (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT
            ) ENGINE=InnoDB;'
        );

        $res = $this->db->dbh->exec('INSERT INTO plugin_docman_item_id (id) SELECT item_id FROM plugin_docman_item');
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while copying item ID to the ID table');
        }

        $res = $this->db->dbh->exec('ALTER TABLE plugin_docman_item CHANGE item_id item_id INT(11) UNSIGNED NOT NULL');
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while removing the AUTOINCREMENT attribute from plugin_docman_item');
        }

        $sql = 'CREATE TABLE plugin_docman_new_document_upload (
                    item_id INT(11) UNSIGNED PRIMARY KEY REFERENCES plugin_docman_item_id(id),
                    expiration_date INT(11) UNSIGNED NOT NULL,
                    parent_id INT(11) UNSIGNED NOT NULL,
                    title TEXT NULL,
                    description TEXT NULL,
                    user_id INT(11) UNSIGNED NOT NULL,
                    filename TEXT NULL,
                    filesize INT(11) UNSIGNED NULL,
                    INDEX idx_parentid (parent_id),
                    INDEX idx_expiration_date (expiration_date)
                ) ENGINE=InnoDB;';
        $this->db->createTable('plugin_docman_new_document_upload', $sql);
    }
}
