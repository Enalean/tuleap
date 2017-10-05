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

class b201710050845_migrate_assigned_to_me_setting_at_tracker_level extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Migrate the assigned to me setting from project level to tracker level';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTableAssignedToMeSettingForMigration();
        $this->migrateDataToTrackerLevel();
        $this->completeTheMigration();
    }

    private function createTableAssignedToMeSettingForMigration()
    {
        $sql = 'DROP TABLE IF EXISTS plugin_tracker_notification_assigned_to_migration;
                CREATE TABLE plugin_tracker_notification_assigned_to_migration (
                  tracker_id INT(11) NOT NULL PRIMARY KEY
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_notification_assigned_to_migration', $sql);
    }

    private function migrateDataToTrackerLevel()
    {
        $sql = 'INSERT INTO plugin_tracker_notification_assigned_to_migration (tracker_id)
                SELECT tracker.id
                FROM plugin_tracker_notification_assigned_to
                JOIN tracker ON (plugin_tracker_notification_assigned_to.project_id = tracker.group_id)';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while migrating setting assigned to me to tracker level'
            );
        }
    }

    private function completeTheMigration()
    {
        $sql         = 'DROP TABLE plugin_tracker_notification_assigned_to;
                        RENAME TABLE plugin_tracker_notification_assigned_to_migration TO plugin_tracker_notification_assigned_to;';
        $exec_result = $this->db->dbh->exec($sql);

        if ($exec_result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException(
                'Failed while completing the data migration of assigned to me setting'
            );
        }
    }
}
