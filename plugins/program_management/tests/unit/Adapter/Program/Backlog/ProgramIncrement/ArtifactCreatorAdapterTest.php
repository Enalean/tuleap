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

use Mockery as M;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ProgramIncrementFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ArtifactCreatorAdapter
     */
    private $adapter;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TrackerArtifactCreator
     */
    private $creator;

    protected function setUp(): void
    {
        $this->creator = M::mock(TrackerArtifactCreator::class);
        $this->adapter = new ArtifactCreatorAdapter($this->creator);
    }

    public function testItCreatesAnArtifact(): void
    {
        $tracker           = new ProgramTracker(TrackerTestBuilder::aTracker()->build());
        $fields_and_values = $this->buildProgramIncrementFieldsData();
        $user              = UserTestBuilder::aUser()->build();
        $submission_date   = new SubmissionDate(1234567890);

        $this->creator->shouldReceive('create')
            ->once()
            ->with(
                M::type(\Tracker::class),
                $fields_and_values->toFieldsDataArray(),
                $user,
                1234567890,
                false,
                false,
                M::type(ChangesetWithFieldsValidationContext::class)
            )
            ->andReturn(new Artifact(201, 27, 101, 1234567890, false));

        $this->adapter->create($tracker, $fields_and_values, $user, $submission_date);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $tracker           = new ProgramTracker(TrackerTestBuilder::aTracker()->build());
        $fields_and_values = $this->buildProgramIncrementFieldsData();
        $user              = UserTestBuilder::aUser()->build();
        $submission_date   = new SubmissionDate(1234567890);

        $this->creator->shouldReceive('create')->andReturnNull();

        $this->expectException(ArtifactCreationException::class);
        $this->adapter->create($tracker, $fields_and_values, $user, $submission_date);
    }

    private function buildProgramIncrementFieldsData(): ProgramIncrementFields
    {
        return new ProgramIncrementFields(
            1000,
            new ArtifactLinkValue(200),
            1001,
            new TitleValue('Program Increment'),
            1002,
            new DescriptionValue('Super important', 'text'),
            1003,
            new MappedStatusValue([10001]),
            1004,
            new StartDateValue('2020-11-02'),
            1005,
            new EndPeriodValue('2020-11-06')
        );
    }
}
