<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201501211009_add_raised_context_project_columns_in_priority_history_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add has_been_raised, context and project_id columns in tracker_artifact_priority_history table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "TRUNCATE TABLE tracker_artifact_priority_history";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while truncating tracker_artifact_priority_history: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE tracker_artifact_priority_history ADD COLUMN has_been_raised TINYINT(1) NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column has_been_raised to tracker_artifact_priority_history: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE tracker_artifact_priority_history ADD COLUMN context INT(11) NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column context to tracker_artifact_priority_history: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE tracker_artifact_priority_history ADD COLUMN project_id INT(11) NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column project_id to tracker_artifact_priority_history: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE tracker_artifact_priority_history ADD COLUMN moved_artifact_id INT(11) NOT NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column moved_artifact_id to tracker_artifact_priority_history: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('tracker_artifact_priority_history', 'has_been_raised')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding has_been_raised column to tracker_artifact_priority_history table');
        }

        if (! $this->db->columnNameExists('tracker_artifact_priority_history', 'context')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding context column to tracker_artifact_priority_history table');
        }

        if (! $this->db->columnNameExists('tracker_artifact_priority_history', 'project_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding project_id column to tracker_artifact_priority_history table');
        }
    }
}
