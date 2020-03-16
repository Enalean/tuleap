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

class b201202031104_add_shared_field_columns extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add new columns to tracker_field and tracker_field_list_bind_static_value to
manage shared fields.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (!$this->db->columnNameExists('tracker_field', 'original_field_id')) {
            $this->updateFieldTable();
        }
        if (!$this->db->columnNameExists('tracker_field_list_bind_static_value', 'original_value_id')) {
            $this->updateFieldListBindStaticValueTable();
        }
    }

    private function updateFieldTable()
    {
        $sql = "ALTER TABLE tracker_field
                ADD COLUMN original_field_id INT( 11 ) UNSIGNED NOT NULL AFTER notifications";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column original_field_id to tracker_field table: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $sql = "UPDATE tracker_field set original_field_id = id";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while initializing original_field_id with some data: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    private function updateFieldListBindStaticValueTable()
    {
        $sql = "ALTER TABLE tracker_field_list_bind_static_value
                ADD COLUMN original_value_id INT(11) NOT NULL AFTER is_hidden";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column original_value_id to tracker_field_list_bind_static_value table: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $sql = "UPDATE tracker_field_list_bind_static_value set original_value_id = id";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while initializing original_value_id with some data: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('tracker_field', 'original_field_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding column original_field_id to tracker_field table');
        }
        if (!$this->db->columnNameExists('tracker_field_list_bind_static_value', 'original_value_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding column original_field_id to tracker_field table');
        }
    }
}
