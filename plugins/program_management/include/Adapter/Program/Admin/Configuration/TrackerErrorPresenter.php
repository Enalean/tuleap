<?php
/**
 * Copyright (c) Enalean 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\SemanticStatusMissingValuesPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\SemanticStatusNoFieldPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TeamHasNoPlanningPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TitleHasIncorrectTypePresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\WorkFlowErrorPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

/**
 * @psalm-immutable
 */
final class TrackerErrorPresenter
{
    /**
     * @var SemanticErrorPresenter[]
     */
    public array $semantic_errors;
    /**
     * @var RequiredErrorPresenter[]
     */
    public array $required_field_errors;
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $transition_rule_error;
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $transition_rule_date_error;
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $field_dependency_error;
    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    public array $non_submittable_field_errors;
    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    public array $non_updatable_field_errors;
    public bool $has_presenter_errors;
    /**
     * @var TrackerReference[]
     */
    public array $team_tracker_id_errors;
    /**
     * @var TrackerReference[]
     */
    public array $status_missing_in_teams;
    /**
     * @var SemanticStatusNoFieldPresenter[]
     */
    public array $semantic_status_no_field;
    /**
     * @var SemanticStatusMissingValuesPresenter[]
     */
    public array $semantic_status_missing_values;
    public bool $has_status_field_not_defined;
    public bool $has_status_missing_in_teams;
    public bool $has_status_missing_values;
    /**
     * @var TitleHasIncorrectTypePresenter[]
     */
    public array $title_has_incorrect_type_error;
    /**
     * @var MissingArtifactLinkFieldPresenter[]
     */
    public array $missing_artifact_link_fields_errors;
    /**
     * @var TeamHasNoPlanningPresenter[]
     */
    public array $team_no_milestone_planning;
    /**
     * @var TeamHasNoPlanningPresenter[]
     */
    public array $team_no_sprint_planning;
    /**
     * @var int[]
     */
    public array $teams_with_error = [];

    /**
     * @param SemanticErrorPresenter[] $semantic_errors
     * @param RequiredErrorPresenter[] $required_field_errors
     * @param WorkFlowErrorPresenter[] $transition_rule_error
     * @param WorkFlowErrorPresenter[] $transition_rule_date_error
     * @param WorkFlowErrorPresenter[] $field_dependency_error
     * @param FieldsPermissionErrorPresenter[] $non_submittable_field_errors
     * @param FieldsPermissionErrorPresenter[] $non_updatable_field_errors
     * @param TrackerReference[] $team_tracker_id_errors
     * @param TrackerReference[] $status_missing_in_teams
     * @param SemanticStatusNoFieldPresenter[] $semantic_status_no_field
     * @param SemanticStatusMissingValuesPresenter[] $semantic_status_missing_values
     * @param TitleHasIncorrectTypePresenter[] $title_has_incorrect_type_error
     * @param MissingArtifactLinkFieldPresenter[] $missing_artifact_link_fields_errors
     * @param TeamHasNoPlanningPresenter[] $team_no_milestone_planning
     * @param TeamHasNoPlanningPresenter[] $team_no_sprint_planning
     */
    private function __construct(
        array $semantic_errors,
        array $required_field_errors,
        array $transition_rule_error,
        array $transition_rule_date_error,
        array $field_dependency_error,
        array $non_submittable_field_errors,
        array $non_updatable_field_errors,
        array $team_tracker_id_errors,
        array $status_missing_in_teams,
        array $semantic_status_no_field,
        array $semantic_status_missing_values,
        array $title_has_incorrect_type_error,
        array $missing_artifact_link_fields_errors,
        array $team_no_milestone_planning,
        array $team_no_sprint_planning,
        array $teams_with_error,
    ) {
        $has_semantic_errors   = count($semantic_errors) > 0;
        $this->semantic_errors = $semantic_errors;

        $has_required_field_errors   = count($required_field_errors) > 0;
        $this->required_field_errors = $required_field_errors;

        $this->transition_rule_error      = $transition_rule_error;
        $this->transition_rule_date_error = $transition_rule_date_error;
        $this->field_dependency_error     = $field_dependency_error;
        $has_workflow_error               = count($transition_rule_error) > 0
            || count($transition_rule_date_error) > 0
            || count($field_dependency_error) > 0;

        $this->non_submittable_field_errors = $non_submittable_field_errors;
        $this->non_updatable_field_errors   = $non_updatable_field_errors;
        $has_field_permission_errors        = count($non_submittable_field_errors) > 0
            || count($non_updatable_field_errors) > 0;

        $this->team_tracker_id_errors = $team_tracker_id_errors;
        $user_can_not_submit_in_team  = count($team_tracker_id_errors) > 0;

        $this->semantic_status_no_field       = $semantic_status_no_field;
        $this->has_status_field_not_defined   = count($this->semantic_status_no_field) > 0;
        $this->status_missing_in_teams        = $status_missing_in_teams;
        $this->has_status_missing_in_teams    = count($this->status_missing_in_teams) > 0;
        $this->semantic_status_missing_values = $semantic_status_missing_values;
        $this->has_status_missing_values      = count($this->semantic_status_missing_values) > 0;
        $has_semantic_status_errors           = count($status_missing_in_teams) > 0
            || count($semantic_status_no_field) > 0
            || count($semantic_status_missing_values) > 0;

        $this->title_has_incorrect_type_error      = $title_has_incorrect_type_error;
        $this->missing_artifact_link_fields_errors = $missing_artifact_link_fields_errors;

        $has_synchronization_errors = count($title_has_incorrect_type_error) > 0 ||
            count($missing_artifact_link_fields_errors) > 0;

        $this->team_no_milestone_planning = $team_no_milestone_planning;
        $this->team_no_sprint_planning    = $team_no_sprint_planning;

        $has_planning_error = count($this->team_no_milestone_planning) > 0 || count($this->team_no_sprint_planning) > 0;

        $this->has_presenter_errors = $has_semantic_errors
            || $has_required_field_errors
            || $has_workflow_error
            || $has_field_permission_errors
            || $user_can_not_submit_in_team
            || $has_semantic_status_errors
            || $has_synchronization_errors
            || $has_planning_error;


        $this->teams_with_error = $teams_with_error;
    }

    public static function fromTracker(
        ConfigurationErrorsGatherer $errors_gatherer,
        TrackerReference $tracker,
        UserReference $user_identifier,
        ConfigurationErrorsCollector $errors_collector,
    ): ?self {
        $errors_gatherer->gatherConfigurationErrors($tracker, $user_identifier, $errors_collector);

        if (! $errors_collector->hasError()) {
            return null;
        }

        return self::fromAlreadyCollectedErrors($errors_collector);
    }

    public static function fromAlreadyCollectedErrors(ConfigurationErrorsCollector $errors_collector): ?self
    {
        if (! $errors_collector->hasError()) {
            return null;
        }

        $non_submittable_fields = [];
        foreach ($errors_collector->getNonSubmittableFields() as $non_submittable_field) {
            $non_submittable_fields[] = new FieldsPermissionErrorPresenter($non_submittable_field);
        }

        $non_updatable_fields = [];
        foreach ($errors_collector->getNonUpdatableFields() as $non_submittable_field) {
            $non_updatable_fields[] = new FieldsPermissionErrorPresenter($non_submittable_field);
        }

        $missing_artifact_link_fields = [];
        foreach ($errors_collector->getMissingArtifactLinkErrors() as $error) {
            $missing_artifact_link_fields[] = new MissingArtifactLinkFieldPresenter($error);
        }

        $required_field_error = [];
        foreach ($errors_collector->getRequiredFieldsErrors() as $error) {
            $required_field_error[] = new RequiredErrorPresenter($error);
        }

        $semantic_error = [];
        foreach ($errors_collector->getSemanticErrors() as $error) {
            $semantic_error[] = new SemanticErrorPresenter($error);
        }

        return new self(
            $semantic_error,
            $required_field_error,
            $errors_collector->getTransitionRuleError(),
            $errors_collector->getTransitionRuleDateError(),
            $errors_collector->getFieldDependencyError(),
            $non_submittable_fields,
            $non_updatable_fields,
            $errors_collector->getTeamTrackerIdErrors(),
            $errors_collector->getStatusMissingInTeams(),
            $errors_collector->getSemanticStatusNoField(),
            $errors_collector->getSemanticStatusMissingValues(),
            $errors_collector->getTitleHasIncorrectTypeError(),
            $missing_artifact_link_fields,
            $errors_collector->getNoMilestonePlanning(),
            $errors_collector->getNoSprintPlanning(),
            $errors_collector->getTeamsWithError()
        );
    }
}
