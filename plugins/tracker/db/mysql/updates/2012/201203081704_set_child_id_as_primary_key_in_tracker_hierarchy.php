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

class b201203081704_set_child_id_as_primary_key_in_tracker_hierarchy extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Tracker can have only one parent, so child_id can be used as primary key.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tracker_hierarchy 
                    DROP KEY idx,
                    ADD PRIMARY KEY (child_id)";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $error_detail  = implode(', ', $this->db->dbh->errorInfo());
            $error_message = 'An error occured while changing primary key to `child_id` on `tracker_hierarchy`: ' . $error_detail;

            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
