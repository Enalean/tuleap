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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\VerifyFieldPermissions;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeboxCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GatherSynchronizedFields $fields_adapter;
    private Stub|CheckSemantic $semantic_checker;
    private Stub|CheckRequiredField $required_field_checker;
    private Stub|CheckWorkflow $workflow_checker;
    private UserIdentifier $user;
    private ProgramTracker $program_increment_tracker;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;
    private RetrieveTrackerFromFieldStub $retrieve_tracker_from_field;

    protected function setUp(): void
    {
        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::with(1, 'tracker');
        $this->semantic_checker            = $this->createStub(CheckSemantic::class);
        $this->required_field_checker      = $this->createStub(CheckRequiredField::class);
        $this->workflow_checker            = $this->createStub(CheckWorkflow::class);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->user = UserIdentifierStub::buildGenericUser();
        $tracker    = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();

        $this->program_increment_tracker = new ProgramTracker($tracker);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields();

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(true);

        self::assertTrue(
            $this->getChecker(VerifyFieldPermissionsStub::withValidField())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(true)
            )
        );
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->fields_adapter = GatherSynchronizedFieldsStub::withDefaults();
        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::withValidField())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(false);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->fields_adapter = GatherSynchronizedFieldsStub::withDefaults();
        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::withValidField())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->fields_adapter = GatherSynchronizedFieldsStub::withError();

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::withValidField())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        $this->buildSynchronizedFields();

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::userCantSubmit())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields();

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::userCantSubmit())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(true);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields();

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::withValidField())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(true)
            )
        );
    }

    public function testItRunAllTestsEvenIfErrorsAreFound(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(false);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(false);

        $this->buildSynchronizedFields();

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(false);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(false);

        $configuration_errors = new ConfigurationErrorsCollector(true);
        self::assertFalse(
            $this->getChecker(VerifyFieldPermissionsStub::userCantSubmit())->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user,
                $configuration_errors
            )
        );

        self::assertTrue($configuration_errors->hasError());
    }

    private function getMilestoneRetriever(
        bool $user_can_submit_artifact_in_team
    ): RetrievePlanningMilestoneTracker {
        $first_milestone_tracker = $this->createStub(\Tracker::class);
        $first_milestone_tracker->method('userCanSubmitArtifact')->willReturn($user_can_submit_artifact_in_team);
        $first_milestone_tracker->method('getId')->willReturn(1);
        return RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_milestone_tracker);
    }

    private function getChecker(VerifyFieldPermissions $retrieve_field_permissions): TimeboxCreatorChecker
    {
        $field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter,
            new NullLogger(),
            $this->retrieve_tracker_from_field,
            $retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );

        return new TimeboxCreatorChecker(
            $field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            $this->retrieve_tracker_from_field,
            RetrieveUserStub::withGenericUser(),
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
    }

    private function buildSynchronizedFields(): void
    {
        $this->fields_adapter = GatherSynchronizedFieldsStub::withFieldIds(6, 2, 3, 4, 5, 1);
    }

    private function buildTeamTrackers(): TrackerCollection
    {
        $first_team_project = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(104),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        return TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->root_milestone_retriever,
            $first_team_project,
            $this->user
        );
    }

    private function buildProgramAndTeamTrackers(TrackerCollection $team_trackers): SourceTrackerCollection
    {
        return SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
                $this->program_increment_tracker->getFullTracker()
            ),
            ProgramIdentifierBuilder::build(),
            $team_trackers,
            $this->user
        );
    }
}
