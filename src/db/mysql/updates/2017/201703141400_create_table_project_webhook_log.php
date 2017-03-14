<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201703141400_create_table_project_webhook_log extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create table project_webhook_log to store project webhook log';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE project_webhook_log (
                  webhook_id INT(11) UNSIGNED,
                  created_on INT(11) NOT NULL,
                  status TEXT NOT NULL,
                  INDEX idx_webhook_id(webhook_id)
                )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('project_webhook_log')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('project_webhook_log table is missing');
        }
    }
}
