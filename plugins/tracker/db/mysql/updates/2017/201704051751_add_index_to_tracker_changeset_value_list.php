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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b201704051751_add_index_to_tracker_changeset_value_list extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add index to display attachment information in changesets";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->updateTable();
    }

    private function updateTable()
    {
        $sql = "ALTER TABLE tracker_changeset_value_list ADD INDEX idx_bind (bindvalue_id, changeset_value_id)";

        $this->addIndex('tracker_changeset_value_list', 'idx_bind', $sql);
    }

    private function indexNameExists($table_name, $index)
    {
        $sql = 'SHOW INDEX FROM ' . $table_name . ' WHERE Key_name LIKE ' . $this->db->dbh->quote($index);
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function addIndex($table_name, $index, $sql)
    {
        $this->log->info('Add index ' . $table_name);
        if (!$this->indexNameExists($table_name, $index)) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                $info = $this->db->dbh->errorInfo();
                $msg  = 'An error occured adding index to ' . $table_name . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($index . ' successfully added index');
        } else {
            $this->log->info($index . ' already exists');
        }
    }
}
