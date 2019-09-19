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

class b201508271443_add_index_on_system_event_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add index on system_event table in order to speed-up
queries on this table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->log->warn('Following operations might take a while, please be patient...');
        $sql = "ALTER TABLE system_event
                ADD INDEX type_idx (type(20))";
        $this->db->addIndex('system_event', 'type_idx', $sql);
    }
}
