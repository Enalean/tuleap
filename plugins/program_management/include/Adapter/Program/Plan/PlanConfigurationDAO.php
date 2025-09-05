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
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsFeature;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\VerifyCanBePlannedInProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewIterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackersIds;
use Tuleap\ProgramManagement\Domain\Program\Plan\SaveNewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsProjectUsedInPlan;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class PlanConfigurationDAO extends DataAccessObject implements SaveNewPlanConfiguration, VerifyCanBePlannedInProgramIncrement, VerifyIsPlannable, VerifyIsFeature, RetrievePlannableTrackersIds, VerifyIsProjectUsedInPlan, RetrievePlanConfiguration
{
    /**
     * @throws \Throwable
     */
    #[\Override]
    public function save(NewPlanConfiguration $plan): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($plan): void {
            $this->setUpPlan($plan);
            $this->setUpPlanPermissions($plan);
            $this->setUpProgramPlan($plan);
            $this->cleanUpTopBacklogs();
            $this->cleanUpWorkflowPostActions();
        });
    }

    private function setUpPlan(NewPlanConfiguration $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_plan WHERE project_id = ?';
        $this->getDB()->run($sql, $plan->program->id);

        $tracker_ids = $plan->trackers_that_can_be_planned->getTrackerIds();
        if ($tracker_ids === []) {
            return;
        }

        $insert = [];
        foreach ($tracker_ids as $tracker_id_that_can_be_planned) {
            $insert[] = [
                'project_id'           => $plan->program->id,
                'plannable_tracker_id' => $tracker_id_that_can_be_planned,
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_plan', $insert);
    }

    private function setUpPlanPermissions(NewPlanConfiguration $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_can_prioritize_features WHERE project_id = ?';
        $this->getDB()->run($sql, $plan->program->id);

        $user_group_ids = $plan->user_groups_that_can_prioritize->getUserGroupIds();
        if ($user_group_ids === []) {
            return;
        }

        $insert = [];
        foreach ($user_group_ids as $user_group_id_that_can_prioritize) {
            $insert[] = [
                'project_id'    => $plan->program->id,
                'user_group_id' => $user_group_id_that_can_prioritize,
            ];
        }
        $this->getDB()->insertMany('plugin_program_management_can_prioritize_features', $insert);
    }

    private function setUpProgramPlan(NewPlanConfiguration $plan): void
    {
        $sql = 'DELETE FROM plugin_program_management_program WHERE program_project_id = ?';
        $this->getDB()->run($sql, $plan->program->id);

        $insert = [
            'program_project_id'           => $plan->program->id,
            'program_increment_tracker_id' => $plan->program_increment_tracker->id,
        ];

        $plan->iteration_tracker->apply(function (NewIterationTrackerConfiguration $iteration) use (&$insert) {
            $insert['iteration_tracker_id'] = $iteration->id;

            if ($iteration->label !== null) {
                $insert['iteration_label'] = $iteration->label;
            }

            if ($iteration->sub_label !== null) {
                $insert['iteration_sub_label'] = $iteration->sub_label;
            }
        });

        if ($plan->program_increment_tracker->label !== null) {
            $insert['program_increment_label'] = $plan->program_increment_tracker->label;
        }

        if ($plan->program_increment_tracker->sub_label !== null) {
            $insert['program_increment_sub_label'] = $plan->program_increment_tracker->sub_label;
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

    #[\Override]
    public function isPlannable(int $plannable_tracker_id): bool
    {
        $sql = 'SELECT count(*) FROM plugin_program_management_plan WHERE plannable_tracker_id = ?';

        return $this->getDB()->exists($sql, $plannable_tracker_id);
    }

    #[\Override]
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
    #[\Override]
    public function getPlannableTrackersIdOfProgram(int $program_id): array
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

    #[\Override]
    public function isProjectUsedInPlan(ProgramForAdministrationIdentifier $administration_identifier): bool
    {
        $sql = 'SELECT 1
                FROM plugin_program_management_program
                WHERE program_project_id = ?';

        return $this->getDB()->exists($sql, $administration_identifier->id);
    }

    #[\Override]
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

    /**
     * @return Option<PlanConfiguration>
     */
    #[\Override]
    public function retrievePlan(ProgramIdentifier $program_identifier): Option
    {
        $sql_config = <<<EOSQL
        SELECT program_increment_tracker_id,
               program_increment_label,
               program_increment_sub_label,
               iteration_tracker_id,
               iteration_label,
               iteration_sub_label
        FROM plugin_program_management_program
        WHERE program_project_id = ?
        EOSQL;

        $config = $this->getDB()->row($sql_config, $program_identifier->getId());

        if (! $config || $config['program_increment_tracker_id'] === null) {
            return Option::nothing(PlanConfiguration::class);
        }

        $tracker_ids_can_be_planned = [];
        $tracker_rows               = $this->getDB()->run(
            'SELECT plannable_tracker_id FROM plugin_program_management_plan WHERE project_id = ?',
            $program_identifier->getId()
        );
        foreach ($tracker_rows as $row) {
            $tracker_ids_can_be_planned[] = $row['plannable_tracker_id'];
        }

        $user_group_ids_that_can_prioritize = [];
        $user_groups_rows                   = $this->getDB()->run(
            'SELECT user_group_id FROM plugin_program_management_can_prioritize_features WHERE project_id = ?',
            $program_identifier->getId()
        );
        foreach ($user_groups_rows as $row) {
            $user_group_ids_that_can_prioritize[] = $row['user_group_id'];
        }

        return Option::fromValue(PlanConfiguration::fromRaw(
            $program_identifier,
            $config['program_increment_tracker_id'],
            $config['program_increment_label'],
            $config['program_increment_sub_label'],
            Option::fromNullable($config['iteration_tracker_id']),
            $config['iteration_label'],
            $config['iteration_sub_label'],
            $tracker_ids_can_be_planned,
            $user_group_ids_that_can_prioritize
        ));
    }
}
