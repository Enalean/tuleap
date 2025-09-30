<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

class b201802211715_add_primary_key_ugroup_user extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add primary key on the ugroup_user table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->db->primaryKeyExists('ugroup_user')) {
            return;
        }

        $this->createNewUgroupUserTable();
        $this->insertNonDuplicatedRowsInNewTable();
        $this->moveNewTableInPlace();
        $this->removeOldUgroupUserTable();
    }

    private function createNewUgroupUserTable()
    {
        $sql = 'CREATE TABLE ugroup_user_tmp_clean_dup (
                  ugroup_id INT(11) NOT NULL,
                  user_id INT(11) NOT NULL,
                  PRIMARY KEY (ugroup_id, user_id)
                ) ENGINE=InnoDB';
        $this->db->createTable('ugroup_user_tmp_clean_dup', $sql);
    }

    private function insertNonDuplicatedRowsInNewTable()
    {
        $sql = 'INSERT IGNORE ugroup_user_tmp_clean_dup SELECT * FROM ugroup_user';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->removeTemporaryUgroupUserTable();
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while inserting data in new ugroup_user table'
            );
        }
    }

    private function removeTemporaryUgroupUserTable()
    {
        $this->db->dropTable('ugroup_user_tmp_clean_dup');
    }

    private function moveNewTableInPlace()
    {
        $sql = 'RENAME TABLE ugroup_user TO ugroup_user_old, ugroup_user_tmp_clean_dup TO ugroup_user';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->removeTemporaryUgroupUserTable();
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while moving cleaned table ugroup_user in place'
            );
        }
    }

    private function removeOldUgroupUserTable()
    {
        $this->db->dropTable('ugroup_user_old');
    }

    public function postUp()
    {
        return $this->db->primaryKeyExists('ugroup_user');
    }
}
