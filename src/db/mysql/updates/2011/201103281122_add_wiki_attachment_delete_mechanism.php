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

class b201103281122_add_wiki_attachment_delete_mechanism extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the table wiki_attachment_deleted to manage deleted wiki attachment in order to facilitate their restore later
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE wiki_attachment ADD filesystem_name VARCHAR( 255 ) DEFAULT NULL';
        if ($this->db->tableNameExists('wiki_attachment')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column filesystem_name to table wiki_attachment');
            }
        }

        $sql = 'ALTER TABLE wiki_attachment ADD delete_date INT(11) UNSIGNED NULL';
        if ($this->db->tableNameExists('wiki_attachment')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column delete_date to table wiki_attachment');
            }
        }

        $sql = 'CREATE TABLE wiki_attachment_deleted (
                id INT( 11 ) NOT NULL AUTO_INCREMENT ,
                group_id INT( 11 ) NOT NULL ,
                name VARCHAR( 255 ) NOT NULL ,
                filesystem_name VARCHAR( 255 ) DEFAULT NULL,
                delete_date INT(11) UNSIGNED NULL,
                purge_date INT(11) UNSIGNED NULL,
                PRIMARY KEY (id),
                INDEX idx_delete_date (delete_date),
                INDEX idx_purge_date (purge_date)
               );';
        $this->db->createTable('wiki_attachment_deleted', $sql);
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('wiki_attachment', 'filesystem_name')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column filesystem_name not created in wiki_attachment');
        }

        if (! $this->db->columnNameExists('wiki_attachment', 'delete_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('delete_date not created in wiki_attachment');
        }

        if (! $this->db->tableNameExists('wiki_attachment_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('wiki_attachment_deleted table is missing');
        }
    }
}
