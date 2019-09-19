<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201308261539_add_cardfields_semantic_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add CardFields semantic table to the Tuleap database
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_cardwall_semantic_cardfields(
            tracker_id int(11) NOT NULL,
            field_id int(11) NOT NULL,
            rank int(11) NOT NULL,
            PRIMARY KEY id_cardfields(tracker_id, field_id)
        ) ENGINE=InnoDB";

        $this->db->createTable('plugin_cardwall_semantic_cardfields', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_cardwall_semantic_cardfields')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_semantic_cardfields is missing');
        }
    }
}
