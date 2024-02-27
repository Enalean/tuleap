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
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\VerifyFieldPermissions;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

final class RequiredFieldVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_FIELD_ID         = 789;
    private const DESCRIPTION_FIELD_ID   = 3;
    private const STATUS_FIELD_ID        = 4;
    private const START_DATE_FIELD_ID    = 5;
    private const END_PERIOD_FIELD_ID    = 6;
    private const ARTIFACT_LINK_FIELD_ID = 987;
    private RetrieveTrackerFromField $retrieve_tracker_from_field;
    private VerifyFieldPermissions $retrieve_field_permissions;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TrackerFactory
     */
    private $tracker_factory;
    private RetrieveProjectFromTrackerStub $project_retriever;
    private TeamProjectsCollection $teams;
    private SynchronizedFieldFromProgramAndTeamTrackersCollection $collection;
    private UserIdentifierStub $user;

    protected function setUp(): void
    {
        $this->tracker_factory             = $this->createStub(\TrackerFactory::class);
        $tracker                           = TrackerReferenceStub::withDefaults();
        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::withTracker($tracker);
        $this->retrieve_field_permissions  = VerifyFieldPermissionsStub::withValidField();

        $this->teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(147),
            ProjectReferenceStub::withId(148),
        );

        $synchronized_fields = SynchronizedFieldReferencesBuilder::buildWithPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(
                self::TITLE_FIELD_ID,
                self::DESCRIPTION_FIELD_ID,
                self::STATUS_FIELD_ID,
                self::START_DATE_FIELD_ID,
                self::END_PERIOD_FIELD_ID,
                self::ARTIFACT_LINK_FIELD_ID
            )
        );

        $this->project_retriever = RetrieveProjectFromTrackerStub::buildGeneric();
        $this->collection        = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            MessageLog::buildFromLogger(new NullLogger()),
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permissions,
            $this->project_retriever
        );
        $this->collection->add(new SynchronizedFieldFromProgramAndTeamTrackers($synchronized_fields));
        $this->user = UserIdentifierStub::buildGenericUser();
    }

    private function getVerifier(): RequiredFieldVerifier
    {
        return new RequiredFieldVerifier($this->tracker_factory);
    }

    public function testAllowsCreationWhenOnlySynchronizedFieldsAreRequired(): void
    {
        $required_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(self::TITLE_FIELD_ID);
        $required_title->method('getLabel')->willReturn("Title");
        $non_required_artifact_link = ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)->withLabel('artlink')->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([$required_title, $non_required_artifact_link]);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('getName')->willReturn("Tracker 1");
        $tracker->method('getGroupId')->willReturn(101);

        $other_tracker_with_no_required_field = $this->createMock(\Tracker::class);
        $other_tracker_with_no_required_field->method('getId')->willReturn(2);
        $other_tracker_with_no_required_field->method('getName')->willReturn("Tracker 2");
        $other_tracker_with_no_required_field->method('getGroupId')->willReturn(101);
        $other_non_required_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $other_non_required_field->method('isRequired')->willReturn(false);
        $other_tracker_with_no_required_field->method('getFormElementFields')->willReturn(
            [$other_non_required_field]
        );
        $retriever        = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::fromTracker($tracker),
            TrackerReferenceStub::fromTracker($other_tracker_with_no_required_field)
        );
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $trackers         = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $this->teams, $this->user, $errors_collector);
        $this->tracker_factory->method('getTrackerById')->willReturnOnConsecutiveCalls(
            $tracker,
            $other_tracker_with_no_required_field
        );

        $no_other_required_fields = $this->getVerifier()->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $this->collection,
            $errors_collector,
            $this->retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        self::assertTrue($no_other_required_fields);
        self::assertCount(0, $errors_collector->getRequiredFieldsErrors());
    }

    public function testDisallowsCreationWhenAnyFieldIsRequiredAndNotSynchronized(): void
    {
        $teams = TeamProjectsCollectionBuilder::withProjects(ProjectReferenceStub::withId(147));

        $required_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $required_title->method('isRequired')->willReturn(true);
        $required_title->method('getId')->willReturn(self::TITLE_FIELD_ID);
        $required_title->method('getLabel')->willReturn("Title");
        $required_title->method('getTrackerId')->willReturn(412);
        $required_artifact_link = ArtifactLinkFieldBuilder::anArtifactLinkField(790)
            ->withTrackerId(412)
            ->withLabel('artlink')
            ->thatIsRequired()
            ->build();

        $other_required_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $other_required_field->method('isRequired')->willReturn(true);
        $other_required_field->method('getId')->willReturn(self::ARTIFACT_LINK_FIELD_ID);
        $other_required_field->method('getLabel')->willReturn('some_label');
        $other_required_field->method('getTrackerId')->willReturn(412);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(412);
        $tracker->method('getName')->willReturn('Tracker 1');
        $tracker->method('getGroupId')->willReturn(147);
        $tracker->method('getFormElementFields')->willReturn(
            [$required_title, $required_artifact_link, $other_required_field]
        );

        $retriever        = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::fromTracker($tracker)
        );
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $trackers         = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $this->user, $errors_collector);
        $this->tracker_factory->method('getTrackerById')->willReturnOnConsecutiveCalls($tracker);

        $no_other_required_fields = $this->getVerifier()->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            $trackers,
            $this->collection,
            $errors_collector,
            $this->retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        self::assertFalse($no_other_required_fields);
        self::assertCount(1, $errors_collector->getRequiredFieldsErrors());
    }
}
