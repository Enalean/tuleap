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

class b201103081738_add_column_filepath_to_frs_file extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add file_path to the table frs_file to dissociate name of the file stored in the filesystem from the one displayed & used in the download.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE frs_file ADD filepath VARCHAR(255) AFTER filename';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column filepath to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD filepath VARCHAR(255) AFTER filename';
        if ($this->db->tableNameExists('frs_file_deleted')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column filepath to table frs_file_delete');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('frs_file', 'filepath')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('filepath not created in frs_file');
        }

        if (! $this->db->columnNameExists('frs_file_deleted', 'filepath')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('filepath not created in frs_file_deleted');
        }
    }
}
