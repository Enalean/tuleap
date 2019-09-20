<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class b201012240808_add_table_frs_log extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the table frs_log to store actions on FRS elements.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE frs_log (
                  log_id int(11) NOT NULL auto_increment,
                  time int(11) NOT NULL default 0,
                  user_id int(11) NOT NULL default 0,
                  group_id int(11) NOT NULL default 0,
                  item_id int(11) NOT NULL,
                  action_id int(11) NOT NULL,
                  PRIMARY KEY (log_id),
                  KEY idx_frs_log_group_item (group_id, item_id)
                );";
        $this->db->createTable('frs_log', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('frs_log')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('frs_log table is missing');
        }
    }
}
