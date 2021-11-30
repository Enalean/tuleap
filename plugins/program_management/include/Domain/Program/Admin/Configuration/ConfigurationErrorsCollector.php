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

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\ProjectReference;

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
     * @var TrackerReference[]
     */
    private array $team_tracker_id_errors = [];
    /**
     * @var SemanticStatusNoFieldPresenter[]
     */
    private array $semantic_status_no_field = [];
    /**
     * @var TrackerReference[]
     */
    private array $status_missing_in_teams = [];
    /**
     * @var SemanticStatusMissingValuesPresenter[]
     */
    private array $semantic_status_missing_values = [];
    /**
     * @var TitleHasIncorrectTypePresenter[]
     */
    private array $title_has_incorrect_type_error = [];
    /**
     * @var MissingArtifactLinkFieldPresenter[]
     */
    private array $missing_artifact_link = [];
    /**
     * @var TeamHasNoPlanningPresenter[]
     */
    private array $no_milestone_planning = [];
    /**
     * @var TeamHasNoPlanningPresenter[]
     */
    private array $no_sprint_planning = [];


    public function __construct(bool $should_collect_all_issues)
    {
        $this->should_collect_all_issues = $should_collect_all_issues;
    }

    public function shouldCollectAllIssues(): bool
    {
        return $this->should_collect_all_issues;
    }

    /**
     * @param TrackerReference[] $potential_trackers_in_error
     */
    public function addSemanticError(
        string $semantic_name,
        string $semantic_short_name,
        array $potential_trackers_in_error,
    ): void {
        $this->semantic_errors[] = new SemanticErrorPresenter(
            $semantic_name,
            $semantic_short_name,
            $potential_trackers_in_error
        );
    }

    public function addRequiredFieldError(TrackerReference $tracker_reference, ProjectReference $project_reference, int $field_id, string $field_label): void
    {
        $this->required_fields_errors[] = new RequiredErrorPresenter($field_id, $field_label, $tracker_reference, $project_reference);
    }

    public function addWorkflowTransitionRulesError(TrackerReference $tracker_reference, ProjectReference $project_reference): void
    {
        $this->transition_rule_error[] = new WorkFlowErrorPresenter(
            $tracker_reference,
            $project_reference,
            '/plugins/tracker/workflow/' . urlencode((string) $tracker_reference->getId()) . '/transitions'
        );
    }

    public function addWorkflowTransitionDateRulesError(TrackerReference $tracker_reference, ProjectReference $project_reference): void
    {
        $this->transition_rule_date_error[] = new WorkFlowErrorPresenter(
            $tracker_reference,
            $project_reference,
            '/plugins/tracker/?tracker=' . urlencode((string) $tracker_reference->getId()) . '&func=admin-workflow'
        );
    }

    public function addWorkflowDependencyError(TrackerReference $tracker_reference, ProjectReference $project_reference): void
    {
        $this->field_dependency_error[] = new WorkFlowErrorPresenter(
            $tracker_reference,
            $project_reference,
            '/plugins/tracker/?tracker=' . urlencode((string) $tracker_reference->getId()) . '&func=admin-dependencies'
        );
    }

    public function addSubmitFieldPermissionError(int $field_id, string $label, TrackerReference $tracker, ProjectReference $project_reference): void
    {
        $this->non_submittable_fields[] = new FieldsPermissionErrorPresenter($field_id, $label, $tracker, $project_reference);
    }

    public function addUpdateFieldPermissionError(int $field_id, string $label, TrackerReference $tracker, ProjectReference $project_reference): void
    {
        $this->non_updatable_fields[] = new FieldsPermissionErrorPresenter($field_id, $label, $tracker, $project_reference);
    }

    public function userCanNotSubmitInTeam(TrackerReference $team_tracker_id): void
    {
        $this->team_tracker_id_errors[] = $team_tracker_id;
    }

    public function addSemanticNoStatusFieldError(int $tracker_id): void
    {
        $this->semantic_status_no_field[] = new SemanticStatusNoFieldPresenter($tracker_id);
    }

    /**
     * @param TrackerReference[] $trackers
     */
    public function addMissingSemanticInTeamErrors(array $trackers): void
    {
        $this->status_missing_in_teams = $trackers;
    }

    /**
     * @param TrackerReference[] $trackers
     */
    public function addMissingValueInSemantic(array $missing_values, array $trackers): void
    {
        $this->semantic_status_missing_values[] = new SemanticStatusMissingValuesPresenter($missing_values, $trackers);
    }

    public function addTitleHasIncorrectType(string $semantic_title_url, string $tracker_name, string $project_name, string $field_name): void
    {
        $this->title_has_incorrect_type_error[] =  new TitleHasIncorrectTypePresenter($semantic_title_url, $tracker_name, $project_name, $field_name);
    }


    public function addMissingFieldArtifactLink(string $field_administration_url, string $tracker_name, string $project_name): void
    {
        $this->missing_artifact_link[] =  new MissingArtifactLinkFieldPresenter($field_administration_url, $tracker_name, $project_name);
    }

    public function addTeamMilestonePlanningNotFoundOrNotAccessible(ProjectReference $project_reference): void
    {
        $this->no_milestone_planning[] = new TeamHasNoPlanningPresenter($project_reference);
    }

    public function addTeamSprintPlanningNotFoundOrNotAccessible(ProjectReference $project): void
    {
        $this->no_sprint_planning[] = new TeamHasNoPlanningPresenter($project);
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
            count($this->semantic_status_missing_values) > 0 ||
            count($this->missing_artifact_link) > 0 ||
            count($this->title_has_incorrect_type_error) > 0 ||
            count($this->no_milestone_planning) > 0 ||
            count($this->no_sprint_planning) > 0;
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
     * @return TrackerReference[]
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
     * @return TrackerReference[]
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

    /**
     * @return TitleHasIncorrectTypePresenter[]
     */
    public function getTitleHasIncorrectTypeError(): array
    {
        return $this->title_has_incorrect_type_error;
    }

    /**
     * @return MissingArtifactLinkFieldPresenter[]
     */
    public function getMissingArtifactLinkErrors(): array
    {
        return $this->missing_artifact_link;
    }

    /**
     * @return TeamHasNoPlanningPresenter[]
     */
    public function getNoMilestonePlanning(): array
    {
        return $this->no_milestone_planning;
    }

    /**
     * @return TeamHasNoPlanningPresenter[]
     */
    public function getNoSprintPlanning(): array
    {
        return $this->no_sprint_planning;
    }
}
