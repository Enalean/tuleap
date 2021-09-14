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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SubmissionDateStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SOURCE_PROGRAM_INCREMENT_ID = 101;
    private const SUBMISSION_TIMESTAMP        = 1234567890;

    private ArtifactCreatorAdapter $adapter;
    private MockObject|TrackerArtifactCreator $creator;
    private ProgramTracker $tracker;
    private \PFUser $user;
    private SubmissionDateStub $submission_date;

    protected function setUp(): void
    {
        $tracker_factory       = $this->createStub(\TrackerFactory::class);
        $this->creator         = $this->createMock(TrackerArtifactCreator::class);
        $this->adapter         = new ArtifactCreatorAdapter($this->creator, $tracker_factory);
        $full_tracker          = TrackerTestBuilder::aTracker()->build();
        $this->tracker         = ProgramTrackerStub::withDefaults();
        $this->user            = UserTestBuilder::aUser()->build();
        $this->submission_date = SubmissionDateStub::withDate(self::SUBMISSION_TIMESTAMP);
        $tracker_factory->method('getTrackerById')->willReturn($full_tracker);
    }

    public function testItCreatesAnArtifact(): void
    {
        $changeset = $this->buildProgramIncrementChangeset();

        $this->creator->expects(self::once())
            ->method('create')
            ->with(
                self::isInstanceOf(\Tracker::class),
                $changeset->toFieldsDataArray(),
                $this->user,
                self::SUBMISSION_TIMESTAMP,
                false,
                false,
                self::isInstanceOf(ChangesetWithFieldsValidationContext::class)
            )
            ->willReturn(new Artifact(201, 27, 101, self::SUBMISSION_TIMESTAMP, false));

        $this->adapter->create($this->tracker, $changeset, $this->user, $this->submission_date);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $this->creator->method('create')->willReturn(null);

        $this->expectException(ArtifactCreationException::class);
        $this->adapter->create(
            $this->tracker,
            $this->buildProgramIncrementChangeset(),
            $this->user,
            $this->submission_date
        );
    }

    private function buildProgramIncrementChangeset(): MirroredTimeboxChangesetValues
    {
        $source_values       = SourceTimeboxChangesetValuesBuilder::buildWithValues(
            'Program Increment',
            'Super important',
            'text',
            ['Current'],
            '2020-11-02',
            '2020-11-06',
            self::SOURCE_PROGRAM_INCREMENT_ID
        );
        $artifact_link_value = ArtifactLinkValue::fromSourceTimeboxValues($source_values);
        $target_fields       = SynchronizedFieldReferencesBuilder::build();

        return MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            MapStatusByValueStub::withValues(10001),
            $source_values,
            $artifact_link_value,
            $target_fields
        );
    }
}
