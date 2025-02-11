<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RemainingEffortValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private \Tracker_FormElementFactory & MockObject $form_element_factory;
    private RemainingEffortValueRetriever $remaining_effort_retriever;
    private Artifact $artifact;
    private \Tracker $tracker;
    private \Tracker_FormElement_Field $field;

    public function setUp(): void
    {
        $this->form_element_factory       = $this->createMock(Tracker_FormElementFactory::class);
        $this->remaining_effort_retriever = new RemainingEffortValueRetriever($this->form_element_factory);
        $this->user                       = UserTestBuilder::buildWithDefaults();

        $this->tracker = TrackerTestBuilder::aTracker()->build();
    }

    private function getRemainingEffortValue(): ?float
    {
        return $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);
    }

    public function testItReturnsTheFloatRemainingEffortValue(): void
    {
        $this->setUpField();
        $this->setUpChangesetValue('6.7001');

        self::assertSame(6.7001, $this->getRemainingEffortValue());
    }

    public function testItReturnsNullWhenTheChangesetValueIsEmpty(): void
    {
        $this->setUpField();
        $this->setUpChangesetValue(null);

        self::assertNull($this->getRemainingEffortValue());
    }

    public function testItReturnsTheIntRemainingEffortValueConvertedToFloat(): void
    {
        $value = 76;

        $this->field = IntFieldBuilder::anIntField(39)
            ->withName(\Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->build();
        $this->form_element_factory->method('getNumericFieldByNameForUser')->willReturn($this->field);

        $changeset       = ChangesetTestBuilder::aChangeset(775)->build();
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Integer(376, $changeset, $this->field, true, $value);
        $changeset->setFieldValue($this->field, $changeset_value);
        $this->artifact = ArtifactTestBuilder::anArtifact(86)
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->build();

        self::assertSame(76.0, $this->getRemainingEffortValue());
    }

    public function testItReturnsNullWhenThereIsNoLastChangeset(): void
    {
        $field = FloatFieldBuilder::aFloatField(760)
            ->withName(\Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->build();
        $this->form_element_factory->method('getNumericFieldByNameForUser')->willReturn($field);
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('getLastChangeset')->willReturn(null);

        self::assertNull($this->getRemainingEffortValue());
    }

    public function testItReturnsNullWhenThereIsNoChangesetValue(): void
    {
        $this->setUpField();
        $changeset = ChangesetTestBuilder::aChangeset(775)->build();
        $changeset->setFieldValue($this->field, null);
        $this->artifact = ArtifactTestBuilder::anArtifact(86)
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->build();

        self::assertNull($this->getRemainingEffortValue());
    }

    public function testItReturnsNullWhenThereIsNoField(): void
    {
        $this->form_element_factory->method('getNumericFieldByNameForUser')->willReturn(null);
        $this->artifact = ArtifactTestBuilder::anArtifact(86)
            ->inTracker($this->tracker)
            ->build();

        self::assertNull($this->getRemainingEffortValue());
    }

    private function setUpField(): void
    {
        $this->field = FloatFieldBuilder::aFloatField(760)
            ->withName(\Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->build();

        $this->form_element_factory->method('getNumericFieldByNameForUser')->with(
            $this->tracker,
            $this->user,
            'remaining_effort',
        )->willReturn(
            $this->field
        );
    }

    private function setUpChangesetValue(?string $value): void
    {
        $changeset       = ChangesetTestBuilder::aChangeset(775)->build();
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Float(376, $changeset, $this->field, true, $value);
        $changeset->setFieldValue($this->field, $changeset_value);
        $this->artifact = ArtifactTestBuilder::anArtifact(86)
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->build();
    }
}
