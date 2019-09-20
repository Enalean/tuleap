<?php
/**
 * Copyright (c) Enalean SAS 2014. All rights reserved
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

class b201410081156_add_burndown_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add table for burndown form elements';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_field_burndown (
                    field_id INT(11) NOT NULL PRIMARY KEY,
                    use_cache TINYINT DEFAULT 0
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_field_burndown', $sql);

        $sql = "REPLACE INTO tracker_field_burndown (field_id, use_cache)
                    (SELECT id, 0 FROM tracker_field WHERE formElement_type = 'burndown')";
        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_field_burndown')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('table tracker_field_burndown not created');
        }
    }
}
