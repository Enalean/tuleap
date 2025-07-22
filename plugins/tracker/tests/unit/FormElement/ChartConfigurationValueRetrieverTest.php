<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ChartConfigurationValueRetrieverTest extends TestCase
{
    private const CAPACITY = 20;

    private ChartConfigurationFieldRetriever&MockObject $field_retriever;
    private Tracker $tracker;
    private Artifact $artifact_sprint;
    private PFUser $user;
    private ChartConfigurationValueRetriever $configuration_value_retriever;
    private IntegerField&MockObject $capacity_field;


    protected function setUp(): void
    {
        $this->field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->tracker         = TrackerTestBuilder::aTracker()->build();
        $this->artifact_sprint = ArtifactTestBuilder::anArtifact(201)->inTracker($this->tracker)->build();
        $this->user            = UserTestBuilder::buildWithDefaults();

        $this->capacity_field = $this->createMock(IntegerField::class);
        $this->capacity_field->method('getId')->willReturn(645);
        $changeset = ChangesetTestBuilder::aChangeset(452)->build();
        $changeset->setFieldValue(
            $this->capacity_field,
            ChangesetValueIntegerTestBuilder::aValue(1, $changeset, $this->capacity_field)->build()
        );
        $this->artifact_sprint->setLastChangeset($changeset);

        $this->configuration_value_retriever = new ChartConfigurationValueRetriever(
            $this->field_retriever,
            $this->createStub(IComputeTimeframes::class),
            new NullLogger(),
        );
    }

    public function testItReturnsNullWhenCapacityIsEmpty(): void
    {
        $this->field_retriever->method('getCapacityField')->with($this->tracker)->willReturn($this->capacity_field);

        $this->capacity_field->method('getComputedValue')->willReturn(null);

        self::assertNull($this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user));
    }

    public function testItReturnsCapacityWhenCapacityIsSet(): void
    {
        $this->field_retriever->method('getCapacityField')->with($this->tracker)->willReturn($this->capacity_field);

        $this->capacity_field->method('getComputedValue')->willReturn(self::CAPACITY);

        self::assertSame(self::CAPACITY, $this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user));
    }
}
