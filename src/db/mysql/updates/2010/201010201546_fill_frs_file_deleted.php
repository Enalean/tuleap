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

class b201010201546_fill_frs_file_deleted extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Fill frs_file_deleted with already deleted files
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
        if (!$this->db->tableNameExists('frs_file_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('frs_file_deleted table is missing, run b201010191436_add_table_frs_file_deleted before.');
        }
    }

    public function dependsOn()
    {
        return array('b201010191436_add_table_frs_file_deleted');
    }

    public function up()
    {
        $sql = 'INSERT INTO frs_file_deleted(file_id, filename, release_id, type_id, processor_id, release_time, file_size, post_date, status, delete_date, purge_date)' .
               ' SELECT f.file_id, f.filename, f.release_id, f.type_id, f.processor_id, f.release_time, f.file_size, f.post_date, f.status, 370514700, 370514700' .
               ' FROM frs_file f LEFT JOIN frs_file_deleted d USING(file_id)' .
               ' WHERE f.status = "D"' .
               ' AND d.file_id IS NULL';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $err = $this->db->dbh->errorInfo();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while filling frs_file_deleted with deleted frs_files: ' . $err[2] . ' (' . $err[0] . ', ' . $err[1] . ')');
        }
    }
}
