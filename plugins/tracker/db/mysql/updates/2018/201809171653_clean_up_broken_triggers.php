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

class b201809171653_clean_up_broken_triggers extends ForgeUpgrade_Bucket // phpcs:ignore
{

    public function description()
    {
        return 'Remove all broken triggers from database.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->removeNoMoreExistingSourceValue();
        $this->removeNoMoreExistingTargetValue();
        $this->cleanUpBrokenTriggers();
        $this->db->dbh->commit();
    }

    private function removeNoMoreExistingSourceValue()
    {
        $sql = "DELETE tracker_workflow_trigger_rule_static_value.*
                FROM tracker_workflow_trigger_rule_static_value
                  LEFT JOIN tracker_field_list_bind_static_value
                     ON (tracker_workflow_trigger_rule_static_value.value_id = tracker_field_list_bind_static_value.id)
                WHERE tracker_field_list_bind_static_value.id IS NULL;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Deletion of trigger involving no more existing source value failed.');
        }
    }

    private function removeNoMoreExistingTargetValue()
    {
        $sql = "DELETE tracker_workflow_trigger_rule_trg_field_static_value.*
                FROM tracker_workflow_trigger_rule_trg_field_static_value
                  LEFT JOIN tracker_field_list_bind_static_value
                     ON (tracker_workflow_trigger_rule_trg_field_static_value.value_id = tracker_field_list_bind_static_value.id)
                WHERE tracker_field_list_bind_static_value.id IS NULL;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Deletion of trigger involving no more existing target value failed.');
        }
    }

    private function cleanUpBrokenTriggers()
    {
        $sql = "DELETE tracker_workflow_trigger_rule_trg_field_static_value.*
                FROM tracker_workflow_trigger_rule_trg_field_static_value
                  LEFT JOIN tracker_workflow_trigger_rule_static_value
                    ON (tracker_workflow_trigger_rule_trg_field_static_value.rule_id = tracker_workflow_trigger_rule_static_value.id)
                WHERE tracker_workflow_trigger_rule_static_value.id IS NULL;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Clean up of triggers without source value failed.');
        }

        $sql = "DELETE tracker_workflow_trigger_rule_static_value.*
                FROM tracker_workflow_trigger_rule_static_value
                  LEFT JOIN tracker_workflow_trigger_rule_trg_field_static_value
                    ON (tracker_workflow_trigger_rule_trg_field_static_value.rule_id = tracker_workflow_trigger_rule_static_value.id)
                WHERE tracker_workflow_trigger_rule_trg_field_static_value.rule_id IS NULL;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Clean up of triggers without target value failed.');
        }
    }
}
