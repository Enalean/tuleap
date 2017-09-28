<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201709271530_add_column_is_deleted_frs_links extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Add a column is_deleted in the frs_uploaded_links table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'ALTER TABLE frs_uploaded_links ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding is_deleted column in frs_uploaded_links table: ' . implode(', ', $this->db->dbh->errorInfo())
            );
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('frs_uploaded_links', 'is_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding is_deleted column in frs_uploaded_links table'
            );
        }
    }
}
