<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class b201010201624_add_table_plugin_docman_item_deleted extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the table plugin_docman_item_deleted to delay deletion of items by introducing purge date in addittion to delete date.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_docman_item_deleted (' .
                     ' item_id INT(11) UNSIGNED NOT NULL,' .
                     ' parent_id INT(11) UNSIGNED NULL,' .
                     ' group_id INT(11) UNSIGNED NULL,' .
                     ' title TEXT NULL,' .
                     ' description TEXT NULL,' .
                     ' create_date INT(11) UNSIGNED NULL,' .
                     ' update_date INT(11) UNSIGNED NULL,' .
                     ' delete_date INT(11) UNSIGNED NULL,' .
                     ' purge_date INT(11) UNSIGNED NULL,' .
                     ' user_id INT(11) UNSIGNED NULL,' .
                     ' status TINYINT(4) DEFAULT 100 NOT NULL,' .
                     ' obsolescence_date int(11) DEFAULT 0 NOT NULL,' .
                     ' rank INT(11) DEFAULT 0 NOT NULL,' .
                     ' item_type INT(11) UNSIGNED NULL,' .
                     ' link_url TEXT NULL,' .
                     ' wiki_page TEXT NULL,' .
                     ' file_is_embedded INT(11) UNSIGNED NULL,' .
                     ' PRIMARY KEY(item_id))';

        $this->db->createTable('plugin_docman_item_deleted', $sql);

        $sql = 'INSERT INTO plugin_docman_item_deleted (item_id, parent_id, group_id, title, ' .
                        ' description, create_date, update_date, delete_date, purge_date, ' .
                        ' user_id, status, obsolescence_date, rank, item_type, link_url, ' .
                        ' wiki_page, file_is_embedded) ' .
                        ' SELECT item_id, parent_id, group_id, title, ' .
                        ' description, create_date, update_date, delete_date, delete_date, ' .
                        ' user_id, status, obsolescence_date, rank, item_type, link_url,' .
                        ' wiki_page, file_is_embedded ' .
                        ' FROM plugin_docman_item ' .
                        ' WHERE delete_date IS NOT NULL';
        if ($this->db->tableNameExists('plugin_docman_item') && $this->db->tableNameExists('plugin_docman_item_deleted')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured copying from  table plugin_docman_item to plugin_docman_item_deleted');
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_docman_item_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_docman_item_deleted table is missing');
        }
    }
}
