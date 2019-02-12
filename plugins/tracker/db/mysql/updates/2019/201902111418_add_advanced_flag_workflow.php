<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201902111418_add_advanced_flag_workflow extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Add advanced flag to tracker_workflow.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addAdvancedInformationInWorkflowTable();
        $this->markAllWorkflowsAsAdvanced();
    }

    private function addAdvancedInformationInWorkflowTable()
    {
        $sql = 'ALTER TABLE tracker_workflow ADD COLUMN is_advanced tinyint(1) NOT NULL';

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding the is_advanced column');
        }
    }

    private function markAllWorkflowsAsAdvanced()
    {
        $sql = 'UPDATE tracker_workflow
                SET is_advanced = 1';

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while marking all the workflows as advanced');
        }
    }
}
