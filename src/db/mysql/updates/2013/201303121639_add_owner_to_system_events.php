<?php
/**
 * Copyright (c) Enalean SAS 2013. All rights reserved
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

class b201303121639_add_owner_to_system_events extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Add owner to system events
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE system_event ADD COLUMN owner VARCHAR(255) NOT NULL default 'root' AFTER end_date";
        if ($this->db->tableNameExists('system_event')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column owner to table system_event');
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('system_event', 'owner')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column owner not created in system_event');
        }
    }
}
