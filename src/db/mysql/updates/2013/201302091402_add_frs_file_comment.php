<?php
/**
 * Copyright (c) Enalean SAS 2013. All rights reserved
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

class b201302091402_add_frs_file_comment extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Add `comment` field to frs_file table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE frs_file ADD COLUMN comment TEXT NULL AFTER user_id";
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column comment to table frs_file');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('frs_file', 'comment')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column comment not created in system_event');
        }
    }
}
