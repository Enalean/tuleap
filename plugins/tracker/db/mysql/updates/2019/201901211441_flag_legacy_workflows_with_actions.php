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

class b201901211441_flag_legacy_workflows_with_actions extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Flag non active workflows with actions as legacy.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addLegacyInformationInWorkflowTable();
        $this->markNonActiveWorkflowsWithActionsAsLegacy();
    }

    private function addLegacyInformationInWorkflowTable()
    {
        $this->db->alterTable(
            'tracker_workflow',
            'tuleap',
            'is_legacy',
            'ALTER TABLE tracker_workflow ADD COLUMN is_legacy tinyint(1) NOT NULL DEFAULT 0'
        );
    }

    private function markNonActiveWorkflowsWithActionsAsLegacy()
    {
        $sql = 'UPDATE tracker_workflow
  INNER JOIN tracker_workflow_transition ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
  LEFT JOIN tracker_workflow_transition_condition_comment_notempty
  ON (tracker_workflow_transition_condition_comment_notempty.transition_id = tracker_workflow_transition.transition_id)
  LEFT JOIN tracker_workflow_transition_condition_field_notempty
  ON (tracker_workflow_transition_condition_field_notempty.transition_id = tracker_workflow_transition.transition_id)
  LEFT JOIN tracker_workflow_transition_postactions_cibuild
  ON (tracker_workflow_transition_postactions_cibuild.transition_id = tracker_workflow_transition.transition_id)
  LEFT JOIN tracker_workflow_transition_postactions_field_date
  ON (tracker_workflow_transition_postactions_field_date.transition_id = tracker_workflow_transition.transition_id)
  LEFT JOIN tracker_workflow_transition_postactions_field_float
  ON (tracker_workflow_transition_postactions_field_float.transition_id = tracker_workflow_transition.transition_id)
  LEFT JOIN tracker_workflow_transition_postactions_field_int
  ON (tracker_workflow_transition_postactions_field_int.transition_id = tracker_workflow_transition.transition_id)
SET tracker_workflow.is_legacy = 1
WHERE tracker_workflow.is_used = 0 AND
  ((tracker_workflow_transition_condition_comment_notempty.transition_id IS NOT NULL
      AND tracker_workflow_transition_condition_comment_notempty.is_comment_required = 1)
      OR tracker_workflow_transition_condition_field_notempty.transition_id IS NOT NULL
      OR tracker_workflow_transition_postactions_cibuild.id IS NOT NULL
      OR tracker_workflow_transition_postactions_field_date.id IS NOT NULL
      OR tracker_workflow_transition_postactions_field_float.id IS NOT NULL
      OR tracker_workflow_transition_postactions_field_int.id IS NOT NULL)';

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('En error occured while marking the non active workflows with actions as legacy');
        }
    }
}
