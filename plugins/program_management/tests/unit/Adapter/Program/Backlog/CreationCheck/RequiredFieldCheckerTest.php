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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class RequiredFieldCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RequiredFieldChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new RequiredFieldChecker();
    }

    public function testAllowsCreationWhenOnlySynchronizedFieldsAreRequired(): void
    {
        $teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147, 148),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $required_title = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(789);
        $non_required_artifact_link = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $non_required_artifact_link->method('isRequired')->willReturn(false);
        $non_required_artifact_link->method('getId')->willReturn(987);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([$required_title, $non_required_artifact_link]);

        $other_tracker_with_no_required_field = $this->createMock(\Tracker::class);
        $other_non_required_field             = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $other_non_required_field->method('isRequired')->willReturn(false);
        $other_tracker_with_no_required_field->method('getFormElementFields')->willReturn(
            [$other_non_required_field]
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            $tracker,
            $other_tracker_with_no_required_field
        );
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(
            $required_title,
            $non_required_artifact_link
        );
        $collection         = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_field);
        $errors_collector         = new ConfigurationErrorsCollector(false);
        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $collection,
            $errors_collector
        );
        self::assertTrue($no_other_required_fields);
        self::assertCount(0, $errors_collector->getErrorMessages());
    }

    public function testDisallowsCreationWhenAnyFieldIsRequiredAndNotSynchronized(): void
    {
        $teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $required_title = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(789);
        $required_title->method('getTrackerId')->willReturn(412);
        $required_artifact_link = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $required_artifact_link->method('isRequired')->willReturn(true);
        $required_artifact_link->method('getId')->willReturn(790);
        $required_artifact_link->method('getTrackerId')->willReturn(412);

        $other_required_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $other_required_field->method('isRequired')->willReturn(true);
        $other_required_field->method('getId')->willReturn('987');
        $other_required_field->method('getLabel')->willReturn('some_label');
        $other_required_field->method('getTrackerId')->willReturn(412);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(412);
        $tracker->method('getFormElementFields')->willReturn(
            [$required_title, $required_artifact_link, $other_required_field]
        );

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(
            $required_title,
            $required_artifact_link
        );
        $collection         = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_field);

        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($tracker);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $errors_collector         = new ConfigurationErrorsCollector(true);
        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $collection,
            $errors_collector
        );
        self::assertFalse($no_other_required_fields);
        self::assertCount(1, $errors_collector->getErrorMessages());
    }

    private function buildSynchronizedFieldDataFromProgramAndTeamTrackers(
        \Tracker_FormElement_Field_Text $title_field,
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field
    ): SynchronizedFieldFromProgramAndTeamTrackers {
        $artifact_link_field_data = new Field($artifact_link_field);

        $title_field_data = new Field($title_field);

        $description_field_data = new Field(
            new \Tracker_FormElement_Field_Text(
                3,
                89,
                1000,
                'description',
                'Description',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                3
            )
        );

        $status_field_data = new Field(
            new \Tracker_FormElement_Field_Selectbox(
                4,
                89,
                1000,
                'status',
                'Status',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                4
            )
        );

        $start_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date(5, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5)
        );

        $end_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date(6, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6)
        );

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldFromProgramAndTeamTrackers($synchronized_fields);
    }
}
