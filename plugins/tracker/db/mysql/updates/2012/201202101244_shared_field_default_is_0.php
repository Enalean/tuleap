<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201202101244_shared_field_default_is_0 extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
The default original id is 0 for shared fields
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->updateFieldTable();
        $this->updateFieldListBindStaticValueTable();
    }

    private function updateFieldTable()
    {
        $sql = "ALTER TABLE tracker_field ALTER COLUMN original_field_id SET DEFAULT '0'";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while altering column original_field_id in tracker_field table: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $sql = 'UPDATE tracker_field SET original_field_id = 0 WHERE original_field_id = id';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while initializing original_field_id with some data: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    private function updateFieldListBindStaticValueTable()
    {
        $sql = "ALTER TABLE tracker_field_list_bind_static_value ALTER COLUMN original_value_id SET DEFAULT '0'";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while altering column original_value_id in tracker_field_list_bind_static_value table: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $sql = 'UPDATE tracker_field_list_bind_static_value SET original_value_id = 0 WHERE original_value_id = id';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while initializing original_value_id with some data: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
