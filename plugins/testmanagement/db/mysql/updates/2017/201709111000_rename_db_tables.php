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

class b201709111000_rename_db_tables extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Replace trafficlights with testmanagement in DB table names.";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->renamePluginTable();
    }

    private function renamePluginTable()
    {
        $sql         = "RENAME TABLE plugin_trafficlights TO plugin_testmanagement";
        $exec_result = $this->db->dbh->exec($sql);

        if ($exec_result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('Failed renaming plugin_trafficlights to plugin_testmanagement');
        }
    }

    public function postUp()
    {
        if ($this->db->tableNameExists('plugin_testmanagement') === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occurred while renaming table.');
        }
    }
}
