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
 *
 */

class b201809250859_create_log_tables extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add tables to store create_test_env logs';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_create_test_env_activity (
                  user_id INT(11) UNSIGNED NOT NULL,
                  project_id INT(11) UNSIGNED NOT NULL,
                  service VARCHAR(64) DEFAULT '',
                  action  TEXT,
                  time    INT(11) UNSIGNED NOT NULL,
                  INDEX idx_time(time)
                ) ENGINE=InnoDB;
                ";

        $result = $this->db->createTable('plugin_create_test_env_activity', $sql);

        if ($result === false) {
            throw new RuntimeException('Create table plugin_create_test_env_activity failed');
        }
    }
}
