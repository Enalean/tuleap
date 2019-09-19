<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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

class b201512291430_add_indexes_for_docman_statistics extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add missing indexes to speed up statistics generation';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addIndex('plugin_docman_item', 'idx_create_date', 'create_date');
        $this->addIndex('plugin_docman_item', 'idx_delete_date', 'delete_date');
    }

    private function addIndex($table_name, $index_name, $column_name)
    {
        $sql = "ALTER TABLE $table_name ADD INDEX $index_name ($column_name)";
        $this->db->addIndex($table_name, $index_name, $sql);
    }
}
