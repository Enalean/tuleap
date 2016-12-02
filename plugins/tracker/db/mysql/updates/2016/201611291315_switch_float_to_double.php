<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201611291315_switch_float_to_double extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Convert float columns to double';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->switchFloatToDouble('tracker_workflow_transition_postactions_field_float', 'value');
        $this->switchFloatToDouble('tracker_field_float', 'default_value');
        $this->switchFloatToDouble('tracker_field_computed_cache', 'value');
        $this->switchFloatToDouble('tracker_changeset_value_float', 'value');
        $this->switchFloatToDouble('tracker_changeset_value_computedfield_manual_value', 'value');
    }

    private function switchFloatToDouble($table, $column)
    {
        $sql = "ALTER TABLE $table CHANGE $column $column DOUBLE default NULL";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                "An error occured while converting float column '$column' to double in $table"
            );
        }
    }
}
