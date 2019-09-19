<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201206151041_delete_invalid_tracker_hierarchy_data extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Delete invalid tracker hierarchy data';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = <<<SQL
            DELETE h.*
            
            FROM tracker_hierarchy AS h
            LEFT JOIN tracker      AS p ON h.parent_id = p.id AND p.deletion_date IS NULL
            LEFT JOIN tracker      AS c ON h.child_id  = c.id AND c.deletion_date IS NULL
            
            WHERE p.id IS NULL
            OR    c.id IS NULL
            OR    h.parent_id = 0
            OR    h.child_id  = 0
SQL;
        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
