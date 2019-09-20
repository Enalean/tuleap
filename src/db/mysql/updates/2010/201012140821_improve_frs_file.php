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

class b201012140821_improve_frs_file extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add fields to the table frs_file for verification of upload/download integrity and to store releaser.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE frs_file ADD computed_md5 VARCHAR(32)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column computed_md5 to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD computed_md5 VARCHAR(32) AFTER status';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column computed_md5 to table frs_file_delete');
            }
        }

        $sql = 'ALTER TABLE frs_file ADD reference_md5 VARCHAR(32)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column reference_md5 to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD reference_md5 VARCHAR(32) AFTER computed_md5';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column reference_md5 to table frs_file_delete');
            }
        }

        $sql = 'ALTER TABLE frs_file ADD user_id INT(11)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column user_id to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD user_id INT(11) AFTER reference_md5';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column user_id to table frs_file_delete');
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('frs_file', 'computed_md5')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('computed_md5 not created in frs_file');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'computed_md5')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('computed_md5 not created in frs_file_deleted');
        }

        if (!$this->db->columnNameExists('frs_file', 'reference_md5')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('reference_md5 not created in frs_file');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'reference_md5')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('reference_md5 not created in frs_file_deleted');
        }

        if (!$this->db->columnNameExists('frs_file', 'user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_id not created in frs_file');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_id not created in frs_file_deleted');
        }
    }
}
