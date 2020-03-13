<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201603251450_add_index_on_nature_column extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add an index on nature column of the tracker_changeset_value_artifactlink table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tracker_changeset_value_artifactlink ADD INDEX idx_nature (nature(10))";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $info = $this->db->dbh->errorInfo();
            $msg  = 'An error occured adding index idx_nature to tracker_changeset_value_artifactlink: ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
            $this->log->error($msg);
            throw new ForgeUpgrade_Bucket_Db_Exception($msg);
        }
    }
}
