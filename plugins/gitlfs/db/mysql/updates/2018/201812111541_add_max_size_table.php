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

class b201812111541_add_max_size_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add new table for Git LFS file max size';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->createTable();
        $this->addDefaultRow();
        $this->db->dbh->commit();
    }

    private function createTable()
    {
        $this->db->createTable(
            'plugin_gitlfs_file_max_size',
            'CREATE TABLE plugin_gitlfs_file_max_size (
              size INT(11) UNSIGNED NOT NULL PRIMARY KEY
            );'
        );
    }

    private function addDefaultRow()
    {
        $sql = 'INSERT INTO plugin_gitlfs_file_max_size (size) VALUES (536870912);';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while adding default row into plugin_gitlfs_file_max_size');
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
