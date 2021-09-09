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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\VerifyFieldPermissions;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class RequiredFieldCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RequiredFieldChecker $checker;
    private RetrieveTrackerFromField $retrieve_tracker_from_field;
    private VerifyFieldPermissions $retrieve_field_permissions;
    private \PHPUnit\Framework\MockObject\Stub|\TrackerFactory $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory             = $this->createStub(\TrackerFactory::class);
        $this->checker                     = new RequiredFieldChecker($this->tracker_factory);
        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::with(1, 'tracker');
        $this->retrieve_field_permissions  = VerifyFieldPermissionsStub::withValidField();
    }

    public function testAllowsCreationWhenOnlySynchronizedFieldsAreRequired(): void
    {
        $teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147, 148),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $required_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(789);
        $required_title->method('getLabel')->willReturn("Title");
        $non_required_artifact_link = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $non_required_artifact_link->method('isRequired')->willReturn(false);
        $non_required_artifact_link->method('getId')->willReturn(987);
        $non_required_artifact_link->method('getLabel')->willReturn("artlink");

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([$required_title, $non_required_artifact_link]);
        $tracker->method('getId')->willReturn(1);

        $other_tracker_with_no_required_field = $this->createMock(\Tracker::class);
        $other_tracker_with_no_required_field->method('getId')->willReturn(2);
        $other_non_required_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $other_non_required_field->method('isRequired')->willReturn(false);
        $other_tracker_with_no_required_field->method('getFormElementFields')->willReturn(
            [$other_non_required_field]
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            $tracker,
            $other_tracker_with_no_required_field
        );
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());
        $this->tracker_factory->method('getTrackerById')->willReturnOnConsecutiveCalls($tracker, $other_tracker_with_no_required_field);

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers();

        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            new NullLogger(),
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        $collection->add($synchronized_field);
        $errors_collector         = new ConfigurationErrorsCollector(false);
        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $collection,
            $errors_collector,
            $this->retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        self::assertTrue($no_other_required_fields);
        self::assertCount(0, $errors_collector->getRequiredFieldsErrors());
    }

    public function testDisallowsCreationWhenAnyFieldIsRequiredAndNotSynchronized(): void
    {
        $teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $required_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(789);
        $required_title->method('getLabel')->willReturn("Title");
        $required_title->method('getTrackerId')->willReturn(412);
        $required_artifact_link = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $required_artifact_link->method('isRequired')->willReturn(true);
        $required_artifact_link->method('getId')->willReturn(790);
        $required_artifact_link->method('getLabel')->willReturn("artlink");
        $required_artifact_link->method('getTrackerId')->willReturn(412);

        $other_required_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $other_required_field->method('isRequired')->willReturn(true);
        $other_required_field->method('getId')->willReturn(987);
        $other_required_field->method('getLabel')->willReturn('some_label');
        $other_required_field->method('getTrackerId')->willReturn(412);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(412);
        $tracker->method('getFormElementFields')->willReturn(
            [$required_title, $required_artifact_link, $other_required_field]
        );

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers();
        $collection         = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            new NullLogger(),
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        $collection->add($synchronized_field);

        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($tracker);
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());
        $this->tracker_factory->method('getTrackerById')->willReturnOnConsecutiveCalls($tracker);

        $errors_collector         = new ConfigurationErrorsCollector(true);
        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $collection,
            $errors_collector,
            $this->retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        self::assertFalse($no_other_required_fields);
        self::assertCount(1, $errors_collector->getRequiredFieldsErrors());
    }

    private function buildSynchronizedFieldDataFromProgramAndTeamTrackers(): SynchronizedFieldFromProgramAndTeamTrackers
    {
        $synchronized_fields = SynchronizedFieldReferences::fromTrackerIdentifier(
            GatherSynchronizedFieldsStub::withFieldIds(789, 3, 4, 5, 6, 987),
            TrackerIdentifierStub::buildWithDefault()
        );

        return new SynchronizedFieldFromProgramAndTeamTrackers($synchronized_fields);
    }
}
