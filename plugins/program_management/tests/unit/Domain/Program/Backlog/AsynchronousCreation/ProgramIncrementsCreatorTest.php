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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceChangesetValuesCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIncrementsCreator $mirrors_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|BuildSynchronizedFields
     */
    private $synchronized_fields_adapter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CreateArtifact
     */
    private $artifact_creator;
    private MapStatusByValueStub $status_mapper;

    protected function setUp(): void
    {
        $transaction_executor              = new DBTransactionExecutorPassthrough();
        $this->synchronized_fields_adapter = $this->createMock(BuildSynchronizedFields::class);
        $this->artifact_creator            = $this->createMock(CreateArtifact::class);
        $this->status_mapper               = MapStatusByValueStub::withValues(5000);
        $this->mirrors_creator             = new ProgramIncrementsCreator(
            $transaction_executor,
            $this->synchronized_fields_adapter,
            $this->status_mapper,
            $this->artifact_creator
        );
    }

    public function testItCreatesMirrorProgramIncrements(): void
    {
        $copied_values = SourceChangesetValuesCollectionBuilder::build();
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(101, 102),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );
        $current_user  = UserTestBuilder::aUser()->build();
        $retriever     = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $trackers      = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $current_user);

        [$first_tracker, $second_tracker] = $trackers->getTrackers();

        $first_synchronized_fields  = $this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006);
        $second_synchronized_fields = $this->buildSynchronizedFields(2001, 2002, 2003, 2004, 2005, 2006);
        $this->synchronized_fields_adapter->method('build')
            ->willReturnOnConsecutiveCalls($first_synchronized_fields, $second_synchronized_fields);

        $this->artifact_creator->expects(self::atLeast(2))
            ->method('create')
            ->withConsecutive(
                [$first_tracker, self::anything(), $current_user, $copied_values->getSubmittedOn()],
                [$second_tracker, self::anything(), $current_user, $copied_values->getSubmittedOn()]
            );

        $this->mirrors_creator->createProgramIncrements($copied_values, $trackers, $current_user);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $copied_values = SourceChangesetValuesCollectionBuilder::build();
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(101),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );
        $current_user  = UserTestBuilder::aUser()->build();
        $retriever     = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $trackers      = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $current_user);

        $this->synchronized_fields_adapter->method('build')
            ->willReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->artifact_creator->method('create')->willThrowException(new ArtifactCreationException());

        $this->expectException(ProgramIncrementArtifactCreationException::class);
        $this->mirrors_creator->createProgramIncrements($copied_values, $trackers, $current_user);
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        $artifact_link_field_data = new Field(new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new Field(new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new Field(new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new Field(new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new Field(new \Tracker_FormElement_Field_Date($start_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new Field(new \Tracker_FormElement_Field_Date($end_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }
}
