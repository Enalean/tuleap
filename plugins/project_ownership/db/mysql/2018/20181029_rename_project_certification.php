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

class b20181029_rename_project_certification extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Rename the project certification plugin to project ownership';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->updatePluginName();
        $this->renameTable();
    }

    private function updatePluginName()
    {
        $res = $this->db->dbh->exec('UPDATE plugin SET name = "project_ownership" WHERE name = "project_certification"');
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while updating the name of the project certification plugin'
            );
        }
    }

    private function renameTable()
    {
        if ($this->db->tableNameExists('plugin_project_ownership_project_owner')) {
            return;
        }
        $res = $this->db->dbh->exec('ALTER TABLE plugin_project_certification_project_owner RENAME plugin_project_ownership_project_owner');
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while renaming the table plugin_project_ownership_project_owner'
            );
        }
    }
}
