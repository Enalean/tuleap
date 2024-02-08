<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class b201903221356_update_semantic_field_primary_key extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Update the primary key of the plugin_velocity_semantic_field table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->cleanUpMultipleValuesForATracker();
        $this->updatePrimaryKey();
    }

    private function cleanUpMultipleValuesForATracker()
    {
        $sql = 'SELECT *
FROM plugin_velocity_semantic_field
GROUP BY tracker_id
HAVING count(*) > 1';

        foreach ($this->db->dbh->query($sql)->fetchAll() as $row) {
            $tracker_id = $row['tracker_id'];
            $field_id   = $row['field_id'];

            $sql_delete_other_entries_for_tracker = "DELETE FROM plugin_velocity_semantic_field
WHERE tracker_id = $tracker_id
  AND field_id <> $field_id";

            if ($this->db->dbh->exec($sql_delete_other_entries_for_tracker) === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                    'An error occured while deleting duplicated entries for tracker in plugin_velocity_semantic_field table.'
                );
            }
        }
    }

    private function updatePrimaryKey()
    {
        $sql_alter_table = 'ALTER TABLE plugin_velocity_semantic_field DROP PRIMARY KEY, ADD PRIMARY KEY(tracker_id)';

        if ($this->db->dbh->exec($sql_alter_table) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occured while updating the primary of plugin_velocity_semantic_field table.'
            );
        }
    }
}
