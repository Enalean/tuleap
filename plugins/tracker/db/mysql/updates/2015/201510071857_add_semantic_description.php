<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class b201510071857_add_semantic_description extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add table to store semantic description";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_semantic_description (
                    tracker_id INT(11) NOT NULL PRIMARY KEY,
                    field_id INT(11) NOT NULL,
                    INDEX filed_id_idx(field_id)
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_semantic_description', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_semantic_description')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_semantic_description table is missing');
        }
    }
}
