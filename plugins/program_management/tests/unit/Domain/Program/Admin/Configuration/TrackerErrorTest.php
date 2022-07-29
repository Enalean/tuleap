<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\PHPUnit\TestCase;

final class TrackerErrorTest extends TestCase
{
    private VerifyIsTeamStub $verify_is_team;

    protected function setUp(): void
    {
        $this->verify_is_team = VerifyIsTeamStub::withValidTeam();
    }

    public function testTrackerHasErrorWhenASemanticIsMisconfigured(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addSemanticError(
            'Title',
            'title',
            [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2), TrackerReferenceStub::withId(3)]
        );
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenAFieldIsRequired(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addRequiredFieldError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric(), 100, 'My field');
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenAWorkflowIsDefined(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addWorkflowTransitionRulesError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenATransitionRuleExistsOnADate(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addWorkflowTransitionDateRulesError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenAFieldDependencyIsSet(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addWorkflowDependencyError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenAFieldIsNotSubmittable(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addSubmitFieldPermissionError(100, "My custom field", TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenAFieldIsNotUpdatable(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addUpdateFieldPermissionError(100, "My custom field", TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenUserCanNotSubmitInATeam(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->userCanNotSubmitInTeam(TrackerReferenceStub::withId(200));
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenSemanticStatusIsNotLinkedTaAField(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addMissingSemanticInTeamErrors([TrackerReferenceStub::withId(1)]);
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertTrue($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenStatusIsNotDefinedInATeam(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addSemanticNoStatusFieldError(TrackerReferenceStub::withId(1));
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertTrue($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenStatusValueIsMissingInATeam(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addMissingValueInSemantic(['Planned', 'On going'], [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2)]);
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertTrue($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenTitleIsATextField(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addTitleHasIncorrectType("admin_url", TrackerReferenceStub::withDefaults(), "project_name", "text_field_name");
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenThereIsNoArtifactLinkField(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addMissingFieldArtifactLink("admin_url", TrackerReferenceStub::withDefaults(), "project_name");
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenTeamHasNoMilestonePlanning(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addTeamMilestonePlanningNotFoundOrNotAccessible(ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasErrorWhenTeamHasNoSprintPlanning(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $error_collector->addTeamSprintPlanningNotFoundOrNotAccessible(ProjectReferenceStub::buildGeneric());
        $tracker_error = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertTrue($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }

    public function testTrackerHasNoError(): void
    {
        $error_collector = new ConfigurationErrorsCollector($this->verify_is_team, false);
        $tracker_error   = TrackerError::fromAlreadyCollectedErrors($error_collector);
        self::assertFalse($tracker_error->has_presenter_errors);
        self::assertFalse($tracker_error->has_status_field_not_defined);
        self::assertFalse($tracker_error->has_status_missing_in_teams);
        self::assertFalse($tracker_error->has_status_missing_values);
    }
}
