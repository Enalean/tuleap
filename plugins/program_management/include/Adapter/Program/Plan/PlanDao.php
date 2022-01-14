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
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\VerifyCanBePlannedInProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Plan\Plan;
use Tuleap\ProgramManagement\Domain\Program\Plan\SavePlan;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class PlanDao extends DataAccessObject implements SavePlan, VerifyCanBePlannedInProgramIncrement, RetrievePlannableTrackers, VerifyIsPlannable, VerifyIsFeature
{
    /**
     * @throws \Throwable
     */
    public function save(Plan $plan): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($plan): void {
            $this->setUpPlan($plan);
            $this->setUpPlanPermissions($plan);
            $this->setUpProgramPlan($plan);
            $this->cleanUpTopBacklogs();
            $this->cleanUpWorkflowPostActions();
        });
    }

    private function setUpPlan(Plan $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_plan WHERE project_id = ?';

        $project_id = $plan->getProjectId();
        $this->getDB()->run($sql, $project_id);

        $insert = [];
        foreach ($plan->getPlannableTrackerIds() as $plannable_tracker_id) {
            $insert[] = [
                'project_id'           => $project_id,
                'plannable_tracker_id' => $plannable_tracker_id,
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_plan', $insert);
    }

    private function setUpPlanPermissions(Plan $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_can_prioritize_features WHERE project_id = ?';

        $this->getDB()->run($sql, $plan->getProjectId());

        $insert = [];
        foreach ($plan->getCanPrioritize() as $can_prioritize_ugroup) {
            $insert[] = [
                'project_id'    => $plan->getProjectId(),
                'user_group_id' => $can_prioritize_ugroup->getId(),
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_can_prioritize_features', $insert);
    }

    private function setUpProgramPlan(Plan $plan): void
    {
        $project_id = $plan->getProjectId();

        $sql = 'DELETE FROM plugin_program_management_program WHERE program_project_id = ?';
        $this->getDB()->run($sql, $project_id);

        $insert = [
            'program_project_id'   => $project_id,
            'program_increment_tracker_id' => $plan->getProgramIncrementTracker()->getId(),
        ];

        if ($plan->getIterationTracker()) {
            $insert['iteration_tracker_id'] = $plan->getIterationTracker()->id;

            if ($plan->getIterationTracker()->label !== null) {
                $insert['iteration_label'] = $plan->getIterationTracker()->label;
            }

            if ($plan->getIterationTracker()->sub_label !== null) {
                $insert['iteration_sub_label'] = $plan->getIterationTracker()->sub_label;
            }
        }

        if ($plan->getCustomLabel() !== null) {
            $insert['program_increment_label'] = $plan->getCustomLabel();
        }

        if ($plan->getCustomSubLabel() !== null) {
            $insert['program_increment_sub_label'] = $plan->getCustomSubLabel();
        }

        $this->getDB()->insert('plugin_program_management_program', $insert);
    }

    private function cleanUpTopBacklogs(): void
    {
        $sql_top_backlog_clean_up = 'DELETE plugin_program_management_explicit_top_backlog.*
            FROM plugin_program_management_explicit_top_backlog
                JOIN tracker_artifact ON (tracker_artifact.id = plugin_program_management_explicit_top_backlog.artifact_id)
                JOIN tracker ON (tracker.id = tracker_artifact.tracker_id)
                LEFT JOIN plugin_program_management_plan ON (
                    plugin_program_management_plan.plannable_tracker_id = tracker_id
                )
            WHERE plugin_program_management_plan.plannable_tracker_id IS NULL';

        $this->getDB()->run($sql_top_backlog_clean_up);
    }

    private function cleanUpWorkflowPostActions(): void
    {
        $sql_workflow_post_action_clean_up = 'DELETE plugin_program_management_workflow_action_add_top_backlog.*
            FROM plugin_program_management_workflow_action_add_top_backlog
                 JOIN tracker_workflow_transition ON (plugin_program_management_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                 JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                 JOIN tracker ON (tracker_workflow.tracker_id = tracker.id)
                 LEFT JOIN plugin_program_management_plan ON (
                    plugin_program_management_plan.plannable_tracker_id = tracker_id
                )
            WHERE plugin_program_management_plan.plannable_tracker_id IS NULL';

        $this->getDB()->run($sql_workflow_post_action_clean_up);
    }

    public function isPlannable(int $plannable_tracker_id): bool
    {
        $sql = 'SELECT count(*) FROM plugin_program_management_plan WHERE plannable_tracker_id = ?';

        return $this->getDB()->exists($sql, $plannable_tracker_id);
    }

    public function isFeature(int $potential_feature_id): bool
    {
        $sql = <<<SQL
        SELECT 1 FROM plugin_program_management_plan AS plan
            INNER JOIN tracker_artifact ON tracker_artifact.tracker_id = plan.plannable_tracker_id
            WHERE tracker_artifact.id = ?
        SQL;
        return $this->getDB()->exists($sql, $potential_feature_id);
    }

    /**
     * @return int[]
     */
    public function getPlannableTrackersOfProgram(int $program_id): array
    {
        $sql = 'SELECT plannable_tracker_id FROM plugin_program_management_plan WHERE project_id = ?';

        $rows = $this->getDB()->q($sql, $program_id);
        return array_map(static fn(array $row): int => $row['plannable_tracker_id'], $rows);
    }

    public function isPartOfAPlan(TrackerReference $tracker): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM plugin_program_management_plan
                LEFT JOIN plugin_program_management_program ON (plugin_program_management_plan.project_id = plugin_program_management_program.program_project_id)
                WHERE plannable_tracker_id = ? OR program_increment_tracker_id = ? OR iteration_tracker_id = ?';

        return $this->getDB()->exists($sql, $tracker->getId(), $tracker->getId(), $tracker->getId());
    }

    public function canBePlannedInProgramIncrement(int $feature_id, int $program_increment_id): bool
    {
        $sql  = 'SELECT NULL
                FROM tracker_artifact AS program_increment
                     INNER JOIN tracker AS program_increment_tracker ON program_increment_tracker.id = program_increment.tracker_id
                     INNER JOIN tracker_artifact AS feature
                     INNER JOIN tracker AS feature_tracker ON feature_tracker.id = feature.tracker_id
                     INNER JOIN plugin_program_management_plan AS plan ON (plan.plannable_tracker_id = feature_tracker.id)
                    INNER JOIN plugin_program_management_program AS program
                ON (program.program_increment_tracker_id = program_increment_tracker.id AND program.program_project_id = plan.project_id)
                WHERE program_increment.id = :program_increment_id AND feature.id = :feature_id';
        $rows = $this->getDB()->run($sql, $program_increment_id, $feature_id);

        return count($rows) > 0;
    }
}
