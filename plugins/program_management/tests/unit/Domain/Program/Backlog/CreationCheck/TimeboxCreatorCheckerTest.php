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
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeboxCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Stub|BuildSynchronizedFields
     */
    private $fields_adapter;
    private SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder $field_collection_builder;
    /**
     * @var Stub|CheckSemantic
     */
    private $semantic_checker;
    /**
     * @var Stub|CheckRequiredField
     */
    private $required_field_checker;
    /**
     * @var Stub|CheckWorkflow
     */
    private $workflow_checker;
    private \PFUser $user;
    private ProgramTracker $program_increment_tracker;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;

    protected function setUp(): void
    {
        $this->fields_adapter           = $this->createStub(BuildSynchronizedFields::class);
        $this->field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter,
            new NullLogger()
        );
        $this->semantic_checker         = $this->createStub(CheckSemantic::class);
        $this->required_field_checker   = $this->createStub(CheckRequiredField::class);
        $this->workflow_checker         = $this->createStub(CheckWorkflow::class);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->user = UserTestBuilder::aUser()->build();
        $tracker    = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();

        $this->program_increment_tracker = new ProgramTracker($tracker);
        $this->root_milestone_retriever  = $this->getMilestoneRetriever(true);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(true);

        self::assertTrue(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $this->root_milestone_retriever = $this->getMilestoneRetriever(false);
        $team_trackers                  = $this->buildTeamTrackers();
        $program_and_team_trackers      = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->fields_adapter->method('build')->willThrowException(new FieldRetrievalException(1, 'title'));

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        $this->buildSynchronizedFields(false);

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $team_trackers             = $this->buildTeamTrackers();
        $program_and_team_trackers = $this->buildProgramAndTeamTrackers($team_trackers);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $program_and_team_trackers,
                $team_trackers,
                $this->user
            )
        );
    }

    private function getMilestoneRetriever(
        bool $user_can_submit_artifact_in_team
    ): RetrievePlanningMilestoneTracker {
        $first_milestone_tracker = $this->createStub(\Tracker::class);
        $first_milestone_tracker->method('userCanSubmitArtifact')->willReturn($user_can_submit_artifact_in_team);
        $first_milestone_tracker->method('getId')->willReturn(1);
        return RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_milestone_tracker);
    }

    private function getChecker(): TimeboxCreatorChecker
    {
        return new TimeboxCreatorChecker(
            $this->field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            new NullLogger()
        );
    }

    private function buildSynchronizedFields(bool $submitable): void
    {
        $title_field = $this->createStub(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 1, true, true);
        $title_field_data = new Field($title_field);

        $artifact_link = $this->createStub(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link->method("getLabel")->willReturn('Link');
        $artifact_link->method("getTrackerId")->willReturn(49);
        $this->mockField($artifact_link, 1, $submitable, true);
        $artifact_link_field_data = new Field($artifact_link);

        $description_field = $this->createStub(Tracker_FormElement_Field_Text::class);
        $this->mockField($description_field, 2, true, true);
        $description_field_data = new Field($description_field);

        $status_field = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $this->mockField($status_field, 3, true, true);
        $status_field_data = new Field($status_field);

        $field_start_date = $this->createStub(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_start_date, 4, true, true);
        $start_date_field_data = new Field($field_start_date);

        $field_end_date = $this->createStub(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_end_date, 5, true, true);
        $end_date_field_data = new Field($field_end_date);

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
        $this->fields_adapter->method('build')->willReturn($synchronized_fields);
    }

    private function mockField(Stub $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->method('getId')->willReturn($id);
        $field->method('userCanSubmit')->willReturn($submitable);
        $field->method('userCanUpdate')->willReturn($updatable);
    }

    private function buildTeamTrackers(): TrackerCollection
    {
        $first_team_project = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
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
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->program_increment_tracker->getFullTracker()),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $this->user),
            $team_trackers,
            $this->user
        );
    }
}
