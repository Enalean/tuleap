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
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TrackerError;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

/**
 * @psalm-immutable
 */
final class TrackerErrorPresenter
{
    public bool $has_status_field_not_defined;
    public bool $has_status_missing_in_teams;
    public bool $has_status_missing_values;
    public bool $has_presenter_errors;

    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    public array $non_updatable_field_errors = [];
    /**
     * @var MissingArtifactLinkFieldPresenter[]
     */
    public array $missing_artifact_link_fields_errors = [];
    /**
     * @var RequiredErrorPresenter[]
     */
    public array $required_field_errors = [];
    /**
     * @var SemanticStatusMissingValuesPresenter[]
     */
    public array $semantic_status_missing_values = [];
    /**
     * @var FieldsPermissionErrorPresenter[]
     */
    public array $non_submittable_field_errors = [];
    /**
     * @var SemanticStatusNoFieldPresenter[]
     */
    public array $semantic_status_no_field = [];
    /**
     * @var TeamHasNoPlanningPresenter[]
     */
    public array $team_no_milestone_planning = [];
    /**
     * @var  TeamHasNoPlanningPresenter[]
     */
    public array $team_no_sprint_planning = [];
    /**
     * @var TitleHasIncorrectTypePresenter[]
     */
    public array $title_has_incorrect_type_error = [];
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $transition_rule_error = [];
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $transition_rule_date_error = [];
    /**
     * @var WorkFlowErrorPresenter[]
     */
    public array $field_dependency_error = [];
    /**
     * @var SemanticErrorPresenter[]
     */
    public array $semantic_errors = [];
    /**
     * @var int[]
     */
    public array $teams_with_error = [];
    /**
     * @var TrackerReference[]
     */
    public array $team_tracker_id_errors = [];
    /**
     * @var TrackerReference[]
     */
    public array $status_missing_in_teams = [];

    private function __construct(TrackerError $tracker_error)
    {
        $this->has_status_field_not_defined = $tracker_error->has_status_field_not_defined;
        $this->has_status_missing_in_teams  = $tracker_error->has_status_missing_in_teams;
        $this->has_status_missing_values    = $tracker_error->has_status_missing_values;

        $this->has_presenter_errors = $tracker_error->has_presenter_errors;

        if (! $tracker_error->collector) {
            return;
        }

        foreach ($tracker_error->collector->getTitleHasIncorrectTypeError() as $error) {
            $this->title_has_incorrect_type_error[] = new TitleHasIncorrectTypePresenter($error);
        }

        foreach ($tracker_error->collector->getTransitionRuleError() as $error) {
            $this->transition_rule_error[] = new WorkFlowErrorPresenter($error);
        }

        foreach ($tracker_error->collector->getTransitionRuleDateError() as $error) {
            $this->transition_rule_date_error[] = new WorkFlowErrorPresenter($error);
        }

        foreach ($tracker_error->collector->getFieldDependencyError() as $error) {
            $this->field_dependency_error[] = new WorkFlowErrorPresenter($error);
        }

        foreach ($tracker_error->collector->getNonSubmittableFields() as $non_submittable_field) {
            $this->non_submittable_field_errors[] = new FieldsPermissionErrorPresenter($non_submittable_field);
        }

        foreach ($tracker_error->collector->getNonUpdatableFields() as $non_submittable_field) {
            $this->non_updatable_field_errors[] = new FieldsPermissionErrorPresenter($non_submittable_field);
        }

        foreach ($tracker_error->collector->getMissingArtifactLinkErrors() as $error) {
            $this->missing_artifact_link_fields_errors[] = new MissingArtifactLinkFieldPresenter($error);
        }

        foreach ($tracker_error->collector->getRequiredFieldsErrors() as $error) {
            $this->required_field_errors[] = new RequiredErrorPresenter($error);
        }

        foreach ($tracker_error->collector->getSemanticErrors() as $error) {
            $this->semantic_errors[] = new SemanticErrorPresenter($error);
        }

        foreach ($tracker_error->collector->getSemanticStatusMissingValues() as $error) {
            $this->semantic_status_missing_values[] = new SemanticStatusMissingValuesPresenter($error);
        }

        foreach ($tracker_error->collector->getSemanticStatusNoField() as $error) {
            $this->semantic_status_no_field[] = new SemanticStatusNoFieldPresenter($error);
        }

        foreach ($tracker_error->collector->getNoMilestonePlanning() as $error) {
            $this->team_no_milestone_planning[] = new TeamHasNoPlanningPresenter($error);
        }

        foreach ($tracker_error->collector->getNoSprintPlanning() as $error) {
            $this->team_no_sprint_planning[] = new TeamHasNoPlanningPresenter($error);
        }

        $this->teams_with_error        = $tracker_error->collector->getTeamsWithError();
        $this->team_tracker_id_errors  = $tracker_error->collector->getTeamTrackerIdErrors();
        $this->status_missing_in_teams = $tracker_error->collector->getStatusMissingInTeams();
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

        return self::fromAlreadyCollectedErrors(TrackerError::fromAlreadyCollectedErrors($errors_collector));
    }

    public static function fromAlreadyCollectedErrors(TrackerError $tracker_error): ?self
    {
        return new self($tracker_error);
    }

    public static function fromTrackerError(TrackerError $tracker_error): self
    {
        return new self($tracker_error);
    }
}
