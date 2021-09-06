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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MirroredProgramIncrementChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceChangesetValuesCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
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
    private SourceChangesetValuesCollection $field_values;
    private TrackerCollection $mirrored_program_increment_trackers;
    private \PFUser $user;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->synchronized_fields_adapter = $this->createMock(BuildSynchronizedFields::class);
        $this->artifact_creator            = $this->createMock(CreateArtifact::class);
        $this->mirrors_creator             = new ProgramIncrementsCreator(
            new DBTransactionExecutorPassthrough(),
            $this->synchronized_fields_adapter,
            MapStatusByValueStub::withValues(5000),
            $this->artifact_creator
        );

        $this->user            = UserTestBuilder::aUser()->build();
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->field_values    = SourceChangesetValuesCollectionBuilder::build();

        $teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(101, 102),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $this->mirrored_program_increment_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048),
            $teams,
            $this->user_identifier
        );
    }

    public function testItCreatesMirrorProgramIncrements(): void
    {
        [$first_tracker, $second_tracker] = $this->mirrored_program_increment_trackers->getTrackers();

        $first_synchronized_fields  = SynchronizedFieldsBuilder::buildWithIds(1001, 1002, 1003, 1004, 1005, 1006);
        $second_synchronized_fields = SynchronizedFieldsBuilder::buildWithIds(2001, 2002, 2003, 2004, 2005, 2006);
        $this->synchronized_fields_adapter->method('build')
            ->willReturnOnConsecutiveCalls($first_synchronized_fields, $second_synchronized_fields);

        $this->artifact_creator->expects(self::atLeast(2))
            ->method('create')
            ->withConsecutive(
                [
                    $first_tracker,
                    self::isInstanceOf(MirroredProgramIncrementChangeset::class),
                    $this->user,
                    $this->field_values->getSubmittedOn()
                ],
                [
                    $second_tracker,
                    self::isInstanceOf(MirroredProgramIncrementChangeset::class),
                    $this->user,
                    $this->field_values->getSubmittedOn()
                ]
            );

        $this->mirrors_creator->createProgramIncrements(
            $this->field_values,
            $this->mirrored_program_increment_trackers,
            $this->user
        );
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $this->synchronized_fields_adapter->method('build')
            ->willReturn(SynchronizedFieldsBuilder::buildWithIds(1001, 1002, 1003, 1004, 1005, 1006));
        $this->artifact_creator->method('create')->willThrowException(new ArtifactCreationException());

        $this->expectException(ProgramIncrementArtifactCreationException::class);
        $this->mirrors_creator->createProgramIncrements(
            $this->field_values,
            $this->mirrored_program_increment_trackers,
            $this->user
        );
    }
}
