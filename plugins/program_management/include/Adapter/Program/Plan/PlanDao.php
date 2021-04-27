<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\VerifyCanBePlannedInProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\Plan;
use Tuleap\ProgramManagement\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\ProgramTracker;

final class PlanDao extends DataAccessObject implements PlanStore, VerifyCanBePlannedInProgramIncrement
{
    /**
     * @throws \Throwable
     */
    public function save(Plan $plan): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($plan): void {
            $this->setUpPlan($plan);
            $this->cleanUpTopBacklogs();
            $this->cleanUpWorkflowPostActions();
        });
    }

    private function setUpPlan(Plan $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_plan WHERE program_increment_tracker_id = ?';

        $program_increment_tracker_id = $plan->getProgramIncrementTracker()->getId();
        $this->getDB()->run($sql, $program_increment_tracker_id);

        $insert = [];
        foreach ($plan->getPlannableTrackerIds() as $plannable_tracker_id) {
            $insert[] = [
                'program_increment_tracker_id' => $program_increment_tracker_id,
                'plannable_tracker_id'         => $plannable_tracker_id
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_plan', $insert);
        $this->setUpPlanPermissions($plan);
        $this->setUpPlanLabels($plan);
    }

    private function setUpPlanPermissions(Plan $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_can_prioritize_features WHERE program_increment_tracker_id = ?';

        $program_increment_tracker_id = $plan->getProgramIncrementTracker()->getId();
        $this->getDB()->run($sql, $program_increment_tracker_id);

        $insert = [];
        foreach ($plan->getCanPrioritize() as $can_prioritize_ugroup) {
            $insert[] = [
                'program_increment_tracker_id' => $program_increment_tracker_id,
                'user_group_id'                => $can_prioritize_ugroup->getId()
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_can_prioritize_features', $insert);
    }

    private function setUpPlanLabels(Plan $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_label_program_increment WHERE program_increment_tracker_id = ?';

        $program_increment_tracker_id = $plan->getProgramIncrementTracker()->getId();
        $this->getDB()->run($sql, $program_increment_tracker_id);

        if ($plan->getCustomLabel() === null && $plan->getCustomSubLabel() === null) {
            return;
        }

        $insert = ['program_increment_tracker_id' => $program_increment_tracker_id];

        if ($plan->getCustomLabel() !== null) {
            $insert['label'] = $plan->getCustomLabel();
        }

        if ($plan->getCustomSubLabel() !== null) {
            $insert['sub_label'] = $plan->getCustomSubLabel();
        }

        $this->getDB()->insert('plugin_program_management_label_program_increment', $insert);
    }

    private function cleanUpTopBacklogs(): void
    {
        $sql_top_backlog_clean_up = 'DELETE plugin_program_management_explicit_top_backlog.*
                FROM plugin_program_management_explicit_top_backlog
                JOIN tracker_artifact ON (tracker_artifact.id = plugin_program_management_explicit_top_backlog.artifact_id)
                JOIN tracker ON (tracker.id = tracker_artifact.tracker_id)
                JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id != tracker.id)';
        $this->getDB()->run($sql_top_backlog_clean_up);
    }

    private function cleanUpWorkflowPostActions(): void
    {
        $sql_workflow_post_action_clean_up = 'DELETE plugin_program_management_workflow_action_add_top_backlog.*
                FROM plugin_program_management_workflow_action_add_top_backlog
                JOIN tracker_workflow_transition ON (plugin_program_management_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id != tracker_workflow.tracker_id)';
        $this->getDB()->run($sql_workflow_post_action_clean_up);
    }

    public function isPlannable(int $plannable_tracker_id): bool
    {
        $sql = 'SELECT count(*) FROM plugin_program_management_plan WHERE plannable_tracker_id = ?';

        return $this->getDB()->exists($sql, $plannable_tracker_id);
    }

    public function isPartOfAPlan(ProgramTracker $tracker_data): bool
    {
        $sql = 'SELECT COUNT(*) FROM plugin_program_management_plan WHERE plannable_tracker_id = ? OR program_increment_tracker_id = ?';

        return $this->getDB()->exists($sql, $tracker_data->getTrackerId(), $tracker_data->getTrackerId());
    }

    public function getProgramIncrementTrackerId(int $project_id): ?int
    {
        $sql = 'SELECT program_increment_tracker_id FROM plugin_program_management_plan
                INNER JOIN tracker ON tracker.id = plugin_program_management_plan.program_increment_tracker_id
                    WHERE tracker.group_id = ?';

        $tracker_id = $this->getDB()->single($sql, [$project_id]);
        if (! $tracker_id) {
            return null;
        }

        return $tracker_id;
    }

    /**
     * @psalm-return null|array{label: ?string, sub_label: ?string}
     */
    public function getProgramIncrementLabels(int $program_increment_tracker_id): ?array
    {
        $sql = "SELECT label, sub_label FROM plugin_program_management_label_program_increment WHERE program_increment_tracker_id = ?";
        return $this->getDB()->row($sql, $program_increment_tracker_id);
    }

    public function canBePlannedInProgramIncrement(int $feature_id, int $program_increment_id): bool
    {
        $sql  = 'SELECT NULL
                FROM tracker_artifact AS program_increment
                     INNER JOIN tracker AS program_increment_tracker ON program_increment_tracker.id = program_increment.tracker_id
                     INNER JOIN tracker_artifact AS feature
                     INNER JOIN tracker AS feature_tracker ON feature_tracker.id = feature.tracker_id
                     INNER JOIN plugin_program_management_plan AS plan
                ON (plan.program_increment_tracker_id = program_increment_tracker.id AND plan.plannable_tracker_id = feature_tracker.id)
                WHERE program_increment.id = :program_increment_id AND feature.id = :feature_id';
        $rows = $this->getDB()->run($sql, $program_increment_id, $feature_id);

        return count($rows) > 0;
    }
}
