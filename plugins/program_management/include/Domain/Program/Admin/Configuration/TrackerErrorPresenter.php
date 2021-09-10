<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

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
     * @var ProgramTracker[]
     */
    public array $team_tracker_id_errors;
    /**
     * @var ProgramTracker[]
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
     * @var string[]
     */
    public array $field_synchronisation_errors;
    public bool $has_field_synchronization_errors;

    /**
     * @param SemanticErrorPresenter[]               $semantic_errors
     * @param RequiredErrorPresenter[]               $required_field_errors
     * @param WorkFlowErrorPresenter[]               $transition_rule_error
     * @param WorkFlowErrorPresenter[]               $transition_rule_date_error
     * @param WorkFlowErrorPresenter[]               $field_dependency_error
     * @param FieldsPermissionErrorPresenter[]       $non_submittable_field_errors
     * @param FieldsPermissionErrorPresenter[]       $non_updatable_field_errors
     * @param ProgramTracker[]                       $team_tracker_id_errors
     * @param ProgramTracker[]                       $status_missing_in_teams
     * @param SemanticStatusNoFieldPresenter[]       $semantic_status_no_field
     * @param SemanticStatusMissingValuesPresenter[] $semantic_status_missing_values
     * @param TitleHasIncorrectTypePresenter[]       $title_has_incorrect_type_error
     * @param string[] $field_synchronisation_errors
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
        array $field_synchronisation_errors
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
        $this->title_has_incorrect_type_error = $title_has_incorrect_type_error;

        $this->has_field_synchronization_errors = count($field_synchronisation_errors) > 0;
        $this->field_synchronisation_errors     = $field_synchronisation_errors;

        $this->has_presenter_errors = $has_semantic_errors
            || $has_required_field_errors
            || $has_workflow_error
            || $has_field_permission_errors
            || $user_can_not_submit_in_team
            || $has_semantic_status_errors
            || $title_has_incorrect_type_error
            || $this->has_field_synchronization_errors;
    }

    public static function fromTracker(
        ConfigurationErrorsGatherer $errors_gatherer,
        ProgramTracker $tracker,
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $errors_collector
    ): ?self {
        $errors_gatherer->gatherConfigurationErrors($tracker, $user_identifier, $errors_collector);

        if (! $errors_collector->hasError()) {
            return null;
        }

        return new self(
            $errors_collector->getSemanticErrors(),
            $errors_collector->getRequiredFieldsErrors(),
            $errors_collector->getTransitionRuleError(),
            $errors_collector->getTransitionRuleDateError(),
            $errors_collector->getFieldDependencyError(),
            $errors_collector->getNonSubmittableFields(),
            $errors_collector->getNonUpdatableFields(),
            $errors_collector->getTeamTrackerIdErrors(),
            $errors_collector->getStatusMissingInTeams(),
            $errors_collector->getSemanticStatusNoField(),
            $errors_collector->getSemanticStatusMissingValues(),
            $errors_collector->getTitleHasIncorrectTypeError(),
            $errors_collector->getFieldSynchronisationError()
        );
    }
}
