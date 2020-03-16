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

class b201405060841_add_index_for_semantic_and_artifact extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add Index on semantic and artifact';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (! $this->indexNameExists('tracker_semantic_status', 'idx_field_open')) {
            $sql = 'ALTER TABLE tracker_semantic_status ADD INDEX idx_field_open(field_id, open_value_id)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                $info = $this->db->dbh->errorInfo();
                $msg  = 'An error occured adding index to tracker_semantic_status: ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
        }

        if (! $this->indexNameExists('tracker_artifact', 'idx_id_changeset_id')) {
            $sql = 'ALTER TABLE tracker_artifact ADD INDEX idx_id_changeset_id(id, last_changeset_id);';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                $info = $this->db->dbh->errorInfo();
                $msg  = 'An error occured adding index to tracker_artifact: ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
        }
    }

    public function postUp()
    {
        if (! $this->indexNameExists('tracker_semantic_status', 'idx_field_open')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException("tracker_semantic_status has no idx_field_open index");
        }

        if (! $this->indexNameExists('tracker_artifact', 'idx_id_changeset_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException("tracker_artifact has no idx_id_changeset_id index");
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
