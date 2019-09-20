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

class b201203021527_add_tracker_hierarchy extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add table to store tracker hierarchy
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_hierarchy (
                  parent_id int(11) NOT NULL,
                  child_id int(11) NOT NULL,
                  KEY idx(parent_id, child_id)
                )";
        $this->db->createTable('tracker_hierarchy', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_hierarchy')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_hierarchy table is missing');
        }
    }
}
