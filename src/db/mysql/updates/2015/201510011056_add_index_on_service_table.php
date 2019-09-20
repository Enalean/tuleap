<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201510011056_add_index_on_service_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add index on service table in order to speed-up queries on this table.";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->log->warn('Following operation might take a while, please be patient...');

        if ($this->db->indexNameExists('service', 'idx_short_name')) {
            $this->db->dbh->query("ALTER TABLE service DROP INDEX idx_short_name");
        }

        $sql = "ALTER TABLE service
                ADD INDEX idx_short_name (short_name(10))";

        $this->db->addIndex('service', 'idx_short_name', $sql);
    }
}
