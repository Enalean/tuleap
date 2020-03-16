<?php
/**
 * Copyright (c) STMicroelectronics 2013. All rights reserved
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

class b201403281402_add_index_for_reverse_link extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add Index on artlink to improve lookup of reverse links';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (! $this->indexNameExists('tracker_changeset_value_artifactlink', 'idx_reverse')) {
            $sql = 'ALTER TABLE tracker_changeset_value_artifactlink ADD INDEX idx_reverse (artifact_id, changeset_value_id)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                $info = $this->db->dbh->errorInfo();
                $msg  = 'An error occured adding index to tracker_changeset_value_artifactlink: ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
        }
    }

    public function postUp()
    {
        if (! $this->indexNameExists('tracker_changeset_value_artifactlink', 'idx_reverse')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException("tracker_changeset_value_artifactlink has no reverse link index");
        }
    }

    /**
     * Return true if the given index name on the table already exists into the database
     *
     * @param String $tableName Table name
     * @param String $index     Index
     *
     * @return bool
     */
    private function indexNameExists($tableName, $index)
    {
        $sql = 'SHOW INDEX FROM ' . $tableName . ' WHERE Key_name LIKE ' . $this->db->dbh->quote($index);
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            $res->closeCursor();
            return true;
        } else {
            return false;
        }
    }
}
