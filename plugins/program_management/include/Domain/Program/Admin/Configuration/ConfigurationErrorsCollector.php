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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class ConfigurationErrorsCollector
{
    private bool $should_collect_all_issues;
    /**
     * @var WorkFlowErrorPresenter[]
     */
    private array $transition_rule_error = [];
    /**
     * @var WorkFlowErrorPresenter[]
     */
    private array $transition_rule_date_error = [];
    /**
     * @var WorkFlowErrorPresenter[]
     */
    private array $field_dependency_error = [];
    /**
     * @var SemanticErrorPresenter[]
     */
    private array $semantic_errors = [];
    /**
     * @var RequiredErrorPresenter[]
     */
    private array $required_fields_errors = [];
    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    private array $non_submittable_fields = [];
    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    private array $non_updatable_fields = [];
    /**
     * @var string[]
     */
    private array $field_synchronisation_error = [];
    /**
     * @var ProgramTracker[]
     */
    private array $team_tracker_id_errors = [];
    /**
     * @var SemanticStatusNoFieldPresenter[]
     */
    private array $semantic_status_no_field = [];
    /**
     * @var ProgramTracker[]
     */
    private array $status_missing_in_teams = [];
    /**
     * @var SemanticStatusMissingValuesPresenter[]
     */
    private array $semantic_status_missing_values = [];


    public function __construct(bool $should_collect_all_issues)
    {
        $this->should_collect_all_issues = $should_collect_all_issues;
    }

    public function shouldCollectAllIssues(): bool
    {
        return $this->should_collect_all_issues;
    }

    /**
     * @param ProgramTracker[] $potential_trackers_in_error
     */
    public function addSemanticError(
        string $semantic_name,
        string $semantic_short_name,
        array $potential_trackers_in_error
    ): void {
        $this->semantic_errors[] = new SemanticErrorPresenter(
            $semantic_name,
            $semantic_short_name,
            $potential_trackers_in_error
        );
    }

    public function addRequiredFieldError(TrackerReference $tracker_reference, int $field_id, string $field_label): void
    {
        $this->required_fields_errors[] = new RequiredErrorPresenter($field_id, $field_label, $tracker_reference);
    }

    public function addWorkflowTransitionRulesError(int $tracker_id): void
    {
        $this->transition_rule_error[] = new WorkFlowErrorPresenter($tracker_id);
    }

    public function addWorkflowTransitionDateRulesError(int $tracker_id): void
    {
        $this->transition_rule_date_error[] = new WorkFlowErrorPresenter($tracker_id);
    }

    public function addWorkflowDependencyError(int $tracker_id): void
    {
        $this->field_dependency_error[] = new WorkFlowErrorPresenter($tracker_id);
    }

    public function addSubmitFieldPermissionError(int $field_id, string $label, TrackerReference $tracker): void
    {
        $this->non_submittable_fields[] = new FieldsPermissionErrorPresenter($field_id, $label, $tracker);
    }

    public function addUpdateFieldPermissionError(int $field_id, string $label, TrackerReference $tracker): void
    {
        $this->non_updatable_fields[] = new FieldsPermissionErrorPresenter($field_id, $label, $tracker);
    }

    public function userCanNotSubmitInTeam(ProgramTracker $team_tracker_id): void
    {
        $this->team_tracker_id_errors[] = $team_tracker_id;
    }

    public function addSemanticNoStatusFieldError(int $tracker_id): void
    {
        $this->semantic_status_no_field[] = new SemanticStatusNoFieldPresenter($tracker_id);
    }

    /**
     * @param array ProgramTracker[] $trackers
     */
    public function addMissingSemanticInTeamErrors(array $trackers): void
    {
        $this->status_missing_in_teams = $trackers;
    }

    /**
     * @param array ProgramTracker[] $trackers
     */
    public function addMissingValueInSemantic(array $missing_values, array $trackers): void
    {
        $this->semantic_status_missing_values[] = new SemanticStatusMissingValuesPresenter($missing_values, $trackers);
    }

    public function addFieldSynchronisationError(string $message): void
    {
        $this->field_synchronisation_error[] = $message;
    }

    public function hasError(): bool
    {
        return count($this->semantic_errors) > 0 ||
            count($this->required_fields_errors) > 0 ||
            count($this->transition_rule_error) > 0 ||
            count($this->transition_rule_date_error) > 0 ||
            count($this->field_dependency_error) > 0 ||
            count($this->non_submittable_fields) > 0 ||
            count($this->non_updatable_fields) > 0 ||
            count($this->team_tracker_id_errors) > 0 ||
            count($this->status_missing_in_teams) > 0 ||
            count($this->semantic_status_no_field) > 0 ||
            count($this->field_synchronisation_error) > 0 ||
            count($this->semantic_status_missing_values) > 0;
    }

    /**
     * @return WorkFlowErrorPresenter[]
     */
    public function getTransitionRuleError(): array
    {
        return $this->transition_rule_error;
    }

    /**
     * @return WorkFlowErrorPresenter[]
     */
    public function getTransitionRuleDateError(): array
    {
        return $this->transition_rule_date_error;
    }

    /**
     * @return WorkFlowErrorPresenter[]
     */
    public function getFieldDependencyError(): array
    {
        return $this->field_dependency_error;
    }

    /**
     * @return SemanticErrorPresenter[]
     */
    public function getSemanticErrors(): array
    {
        return $this->semantic_errors;
    }

    /**
     * @return RequiredErrorPresenter[]
     */
    public function getRequiredFieldsErrors(): array
    {
        return $this->required_fields_errors;
    }

    /**
     * @return FieldsPermissionErrorPresenter[]
     */
    public function getNonSubmittableFields(): array
    {
        return $this->non_submittable_fields;
    }

    /**
     * @return FieldsPermissionErrorPresenter[]
     */
    public function getNonUpdatableFields(): array
    {
        return $this->non_updatable_fields;
    }

    /**
     * @return string[]
     */
    public function getFieldSynchronisationError(): array
    {
        return $this->field_synchronisation_error;
    }

    /**
     * @return ProgramTracker[]
     */
    public function getTeamTrackerIdErrors(): array
    {
        return $this->team_tracker_id_errors;
    }

    /**
     * @return SemanticStatusNoFieldPresenter[]
     */
    public function getSemanticStatusNoField(): array
    {
        return $this->semantic_status_no_field;
    }

    /**
     * @return ProgramTracker[]
     */
    public function getStatusMissingInTeams(): array
    {
        return $this->status_missing_in_teams;
    }

    /**
     * @return SemanticStatusMissingValuesPresenter[]
     */
    public function getSemanticStatusMissingValues(): array
    {
        return $this->semantic_status_missing_values;
    }
}
