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

class b201902121635_map_workflow_pre_condiction_new_artifact_with_old_behaviour extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Map the (New artifact) pre conditions to the old behaviour: all_users and not field required.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->deleteFieldNotEmptyPreConditionsFromTransitionBasedOnNewArtifact();
        $this->updateAuthorizedUgroupsFromTransitionBasedOnNewArtifact();
        $this->db->dbh->commit();
    }

    private function deleteFieldNotEmptyPreConditionsFromTransitionBasedOnNewArtifact()
    {
        $sql = 'DELETE tracker_workflow_transition_condition_field_notempty.*
FROM tracker_workflow_transition_condition_field_notempty
   INNER JOIN tracker_workflow_transition ON (tracker_workflow_transition.transition_id = tracker_workflow_transition_condition_field_notempty.transition_id)
   INNER JOIN tracker_workflow ON (tracker_workflow_transition.workflow_id = tracker_workflow.workflow_id)
WHERE tracker_workflow.is_used = 1
  AND tracker_workflow_transition.from_id = 0';

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occured while deleting field not empty pre conditions.');
        }
    }

    private function updateAuthorizedUgroupsFromTransitionBasedOnNewArtifact()
    {
        $sql_all_matching_transitions = 'SELECT tracker_workflow_transition.*
FROM tracker_workflow_transition
   INNER JOIN tracker_workflow ON (tracker_workflow_transition.workflow_id = tracker_workflow.workflow_id)
WHERE tracker_workflow.is_used = 1
  AND tracker_workflow_transition.from_id = 0';

        foreach ($this->db->dbh->query($sql_all_matching_transitions)->fetchAll() as $row) {
            $transition_id = $row['transition_id'];

            $sql_delete_existing_permissions = "DELETE FROM permissions
WHERE permission_type='PLUGIN_TRACKER_WORKFLOW_TRANSITION'
  AND object_id = '$transition_id'";

            $deleted = $this->db->dbh->exec($sql_delete_existing_permissions);
            if ($deleted === false) {
                $this->rollBackOnError('An error occured while deleting existing authorized ugroups pre conditions.');
            }

            $sql_insert_matching_permission = "INSERT INTO permissions (permission_type, object_id, ugroup_id)
VALUES ('PLUGIN_TRACKER_WORKFLOW_TRANSITION', '$transition_id', 1)";

            $insert = $this->db->dbh->exec($sql_insert_matching_permission);
            if ($insert === false) {
                $this->rollBackOnError('An error occured while adding all_users as the authorized ugroup pre conditions.');
            }
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($message);
    }
}
