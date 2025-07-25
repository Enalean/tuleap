<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\Numeric;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_ChangesetValue_Numeric;
use Tracker_FormElement_Field_Numeric;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\NumericFieldWithValue;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueComputedTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFloatTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactIdFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ComputedFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\PerTrackerArtifactIdFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\PriorityFieldBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\Dao\SearchArtifactGlobalRankStub;

#[DisableReturnValueGenerationForTestDoubles]
final class NumericFieldWithValueBuilderTest extends TestCase
{
    private function buildNumericFieldWithValue(
        Tracker_FormElement_Field_Numeric $field,
        ?Tracker_Artifact_ChangesetValue_Numeric $value,
    ): NumericFieldWithValue {
        $builder = new NumericFieldWithValueBuilder(SearchArtifactGlobalRankStub::build()->withArtifactRank(745, 1003));

        return $builder->buildNumericFieldWithValue(
            new ConfiguredField($field, DisplayType::BLOCK),
            ArtifactTestBuilder::anArtifact(745)->withPerTrackerArtifactId(963)->build(),
            $value,
        );
    }

    public function testItBuildsIntField(): void
    {
        $field = IntegerFieldBuilder::anIntField(12)->build();
        $value = ChangesetValueIntegerTestBuilder::aValue(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withValue(23)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(23),
            ),
            $this->buildNumericFieldWithValue($field, $value),
        );
    }

    public function testItBuildsFloatField(): void
    {
        $field = FloatFieldBuilder::aFloatField(12)->build();
        $value = ChangesetValueFloatTestBuilder::aValue(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withValue(9.81)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(9.81),
            ),
            $this->buildNumericFieldWithValue($field, $value),
        );
    }

    public function testItBuildsArtifactIdField(): void
    {
        $field = ArtifactIdFieldBuilder::anArtifactIdField(12)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(745),
            ),
            $this->buildNumericFieldWithValue($field, null),
        );
    }

    public function testItBuildsPerTrackerArtifactIdField(): void
    {
        $field = PerTrackerArtifactIdFieldBuilder::aPerTrackerArtifactIdField(12)->build();
        $value = ChangesetValueIntegerTestBuilder::aValue(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withValue(963)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(963),
            ),
            $this->buildNumericFieldWithValue($field, $value),
        );
    }

    public function testItBuildsPriorityField(): void
    {
        $field = PriorityFieldBuilder::aPriorityField(12)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(1003),
            ),
            $this->buildNumericFieldWithValue($field, null),
        );
    }

    public function testItBuildsComputedField(): void
    {
        $field = ComputedFieldBuilder::aComputedField(12)->build();
        $value = ChangesetValueComputedTestBuilder::aValue(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withValue(2)->build();

        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(2),
            ),
            $this->buildNumericFieldWithValue($field, $value),
        );
    }

    public function testItReturnsNullWhenChangesetIsNull(): void
    {
        $field = IntegerFieldBuilder::anIntField(12)->build();
        self::assertEquals(
            new NumericFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::nothing(\Psl\Type\int()),
            ),
            $this->buildNumericFieldWithValue($field, null),
        );
    }
}
