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

class b201702211530_create_global_parameters_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store the global parameters of the Git plugin";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_git_global_parameters (
                  name VARCHAR(255) NOT NULL PRIMARY KEY,
                  value TEXT NOT NULL
                )";

        $this->db->createTable('plugin_git_global_parameters', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_git_global_parameters')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_git_global_parameters table is missing');
        }
    }
}
