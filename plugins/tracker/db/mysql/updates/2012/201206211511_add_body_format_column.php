<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

class b201206211511_add_body_format_column extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add new column to tracker_changeset_comment to manage follow ups types.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (!$this->db->columnNameExists('tracker_changeset_comment', 'body_format')) {
            $sql = "ALTER TABLE tracker_changeset_comment
                    ADD COLUMN body_format varchar(16) NOT NULL default 'text' AFTER body";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column body_format to tracker_changeset_comment table: ' . implode(', ', $this->db->dbh->errorInfo()));
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('tracker_changeset_comment', 'body_format')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding column original_field_id to tracker_field table');
        }
    }
}
