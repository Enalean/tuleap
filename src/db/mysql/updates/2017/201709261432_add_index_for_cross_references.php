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

class b201709261432_add_index_for_cross_references extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Add indexes for cross_references';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->removeExistingIndexes('source_idx');
        $this->removeExistingIndexes('target_idx');
        $sql = "ALTER TABLE cross_references
                    ADD INDEX source_idx(source_id(10), source_type(10)),
                    ADD INDEX target_idx(target_id(10), target_type(10))";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }

    private function removeExistingIndexes($index_name)
    {
        $sql = "SHOW INDEX FROM cross_references WHERE key_name = '$index_name'";
        $result = $this->db->dbh->query($sql);
        if ($result->fetch() === false) {
            return;
        }

        $sql = "ALTER TABLE cross_references DROP INDEX $index_name";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
