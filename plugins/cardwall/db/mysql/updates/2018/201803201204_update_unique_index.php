<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201803201204_update_unique_index extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update table plugin_cardwall_on_top_column_mapping_field_value with unique index';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_cardwall_on_top_column_mapping_field_value
                DROP PRIMARY KEY, ADD CONSTRAINT idx UNIQUE
                (cardwall_tracker_id, tracker_id, field_id, value_id)";
        $res = $this->db->primaryKeyExists('plugin_cardwall_on_top_column_mapping_field_value');

        if ($res === true) {
            $this->db->dbh->exec($sql);
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_cardwall_on_top_column_mapping_field_value')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_on_top_column_mapping_field_value is missing');
        }
    }
}
