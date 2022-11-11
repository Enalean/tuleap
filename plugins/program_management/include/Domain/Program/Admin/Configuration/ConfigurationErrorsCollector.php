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

use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\ProjectReference;

final class ConfigurationErrorsCollector
{
    /**
     * @var WorkFlowError[]
     */
    private array $transition_rule_error = [];
    /**
     * @var WorkFlowError[]
     */
    private array $transition_rule_date_error = [];
    /**
     * @var WorkFlowError[]
     */
    private array $field_dependency_error = [];
    /**
     * @var SemanticError[]
     */
    private array $semantic_errors = [];
    /**
     * @var RequiredError[]
     */
    private array $required_fields_errors = [];
    /**
     * @var FieldsPermissionError[]
     */
    private array $non_submittable_fields = [];
    /**
     * @var FieldsPermissionError[]
     */
    private array $non_updatable_fields = [];
    /**
     * @var TrackerReference[]
     */
    private array $team_tracker_id_errors = [];
    /**
     * @var SemanticStatusNoField[]
     */
    private array $semantic_status_no_field = [];
    /**
     * @var TrackerReference[]
     */
    private array $status_missing_in_teams = [];
    /**
     * @var SemanticStatusMissingValues[]
     */
    private array $semantic_status_missing_values = [];
    /**
     * @var TitleHasIncorrectType[]
     */
    private array $title_has_incorrect_type_error = [];
    /**
     * @var MissingArtifactLinkField[]
     */
    private array $missing_artifact_link = [];
    /**
     * @var TeamHasNoPlanning[]
     */
    private array $no_milestone_planning = [];
    /**
     * @var TeamHasNoPlanning[]
     */
    private array $no_sprint_planning = [];

    /**
     * @var int[]
     */
    private array $teams_with_error = [];


    public function __construct(private VerifyIsTeam $verify_is_team, private bool $should_collect_all_issues)
    {
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
        $this->semantic_errors[] = new SemanticError(
            $semantic_name,
            $semantic_short_name,
            $potential_trackers_in_error
        );
        $this->addTeamsInErrorIfNeeded($potential_trackers_in_error);
    }

    public function addRequiredFieldError(TrackerReference $tracker, ProjectReference $project, int $field_id, string $field_label): void
    {
        $this->required_fields_errors[] = new RequiredError($field_id, $field_label, $tracker, $project);
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addWorkflowTransitionRulesError(TrackerReference $tracker, ProjectReference $project): void
    {
        $this->transition_rule_error[] = new WorkFlowError(
            $tracker,
            $project,
            '/plugins/tracker/workflow/' . urlencode((string) $tracker->getId()) . '/transitions'
        );
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addWorkflowTransitionDateRulesError(TrackerReference $tracker, ProjectReference $project): void
    {
        $this->transition_rule_date_error[] = new WorkFlowError(
            $tracker,
            $project,
            '/plugins/tracker/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-workflow'
        );
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addWorkflowDependencyError(TrackerReference $tracker, ProjectReference $project): void
    {
        $this->field_dependency_error[] = new WorkFlowError(
            $tracker,
            $project,
            '/plugins/tracker/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-dependencies'
        );
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addSubmitFieldPermissionError(int $field_id, string $label, TrackerReference $tracker, ProjectReference $project): void
    {
        $this->non_submittable_fields[] = new FieldsPermissionError($field_id, $label, $tracker, $project);
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addUpdateFieldPermissionError(int $field_id, string $label, TrackerReference $tracker, ProjectReference $project): void
    {
        $this->non_updatable_fields[] = new FieldsPermissionError($field_id, $label, $tracker, $project);
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function userCanNotSubmitInTeam(TrackerReference $team_tracker): void
    {
        $this->team_tracker_id_errors[] = $team_tracker;
        $this->addTeamInErrorIfNeeded($team_tracker);
    }

    public function addSemanticNoStatusFieldError(TrackerReference $tracker): void
    {
        $this->semantic_status_no_field[] = new SemanticStatusNoField($tracker->getId());
        $this->addTeamInErrorIfNeeded($tracker);
    }

    /**
     * @param TrackerReference[] $trackers
     */
    public function addMissingSemanticInTeamErrors(array $trackers): void
    {
        $this->status_missing_in_teams = $trackers;
        $this->addTeamsInErrorIfNeeded($trackers);
    }

    /**
     * @param TrackerReference[] $trackers
     */
    public function addMissingValueInSemantic(array $missing_values, array $trackers): void
    {
        $this->semantic_status_missing_values[] = new SemanticStatusMissingValues($missing_values, $trackers);
        $this->addTeamsInErrorIfNeeded($trackers);
    }

    public function addTitleHasIncorrectType(string $semantic_title_url, TrackerReference $tracker, string $project_name, string $field_name): void
    {
        $this->title_has_incorrect_type_error[] = new TitleHasIncorrectType($semantic_title_url, $tracker->getLabel(), $project_name, $field_name);
        $this->addTeamInErrorIfNeeded($tracker);
    }


    public function addMissingFieldArtifactLink(string $field_administration_url, TrackerReference $tracker, string $project_name): void
    {
        $this->missing_artifact_link[] = new MissingArtifactLinkField($field_administration_url, $tracker->getLabel(), $project_name);
        $this->addTeamInErrorIfNeeded($tracker);
    }

    public function addTeamMilestonePlanningNotFoundOrNotAccessible(ProjectReference $project): void
    {
        $this->no_milestone_planning[]             = new TeamHasNoPlanning($project);
        $this->teams_with_error[$project->getId()] = $project->getId();
    }

    public function addTeamSprintPlanningNotFoundOrNotAccessible(ProjectReference $project): void
    {
        $this->no_sprint_planning[]                = new TeamHasNoPlanning($project);
        $this->teams_with_error[$project->getId()] = $project->getId();
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
     * @return WorkFlowError[]
     */
    public function getTransitionRuleError(): array
    {
        return $this->transition_rule_error;
    }

    /**
     * @return WorkFlowError[]
     */
    public function getTransitionRuleDateError(): array
    {
        return $this->transition_rule_date_error;
    }

    /**
     * @return WorkFlowError[]
     */
    public function getFieldDependencyError(): array
    {
        return $this->field_dependency_error;
    }

    /**
     * @return SemanticError[]
     */
    public function getSemanticErrors(): array
    {
        return $this->semantic_errors;
    }

    /**
     * @return RequiredError[]
     */
    public function getRequiredFieldsErrors(): array
    {
        return $this->required_fields_errors;
    }

    /**
     * @return FieldsPermissionError[]
     */
    public function getNonSubmittableFields(): array
    {
        return $this->non_submittable_fields;
    }

    /**
     * @return FieldsPermissionError[]
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
     * @return SemanticStatusNoField[]
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
     * @return SemanticStatusMissingValues[]
     */
    public function getSemanticStatusMissingValues(): array
    {
        return $this->semantic_status_missing_values;
    }

    /**
     * @return TitleHasIncorrectType[]
     */
    public function getTitleHasIncorrectTypeError(): array
    {
        return $this->title_has_incorrect_type_error;
    }

    /**
     * @return MissingArtifactLinkField[]
     */
    public function getMissingArtifactLinkErrors(): array
    {
        return $this->missing_artifact_link;
    }

    /**
     * @return TeamHasNoPlanning[]
     */
    public function getNoMilestonePlanning(): array
    {
        return $this->no_milestone_planning;
    }

    /**
     * @return TeamHasNoPlanning[]
     */
    public function getNoSprintPlanning(): array
    {
        return $this->no_sprint_planning;
    }

    private function addTeamInErrorIfNeeded(TrackerReference $tracker): void
    {
        if ($this->verify_is_team->isATeam($tracker->getProjectId())) {
            $this->teams_with_error[$tracker->getProjectId()] = $tracker->getProjectId();
        }
    }

    /**
     * @param TrackerReference[] $trackers
     */
    private function addTeamsInErrorIfNeeded(array $trackers): void
    {
        foreach ($trackers as $tracker) {
            if ($this->verify_is_team->isATeam($tracker->getProjectId())) {
                $this->teams_with_error[$tracker->getProjectId()] = $tracker->getProjectId();
            }
        }
    }

    /**
     * @return int[]
     */
    public function getTeamsWithError(): array
    {
        return $this->teams_with_error;
    }
}
