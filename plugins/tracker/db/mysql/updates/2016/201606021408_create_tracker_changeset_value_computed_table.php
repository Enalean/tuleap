<?php
/**
 * Copyright (c) Enalean SAS 2016. All rights reserved
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

class b201606021408_create_tracker_changeset_value_computed_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Create table tracker_changeset_value_computedfield_manual_value';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_changeset_value_computedfield_manual_value (
                    changeset_value_id INT(11) NOT NULL,
                    value FLOAT(10,4),
                    PRIMARY KEY(changeset_value_id)
                ) ENGINE=InnoDB";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while creating tracker_changeset_value_computedfield_manual_value: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_changeset_value_computedfield_manual_value')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Table tracker_changeset_value_computedfield_manual_value not created');
        }
    }
}
