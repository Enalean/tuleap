<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\List;

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Color\ItemColor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueOpenListBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\OpenListValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\StaticBindDecoratorBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StaticListFieldWithValueBuilderTest extends TestCase
{
    private const TRACKER_ID = 65453;
    private Tracker $tracker;
    private \Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(168)->build();
        $this->tracker   = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withProject($project)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(78)->inTracker($this->tracker)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1263)->ofArtifact($artifact)->build();
    }

    public function testItReturnsEmptyValuesWhenNoneIsSelected(): void
    {
        $list_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(123)->inTracker($this->tracker)->withLabel('static list field')->build(),
        )->withBuildStaticValues([
            ListStaticValueBuilder::aStaticValue('Something')->build(),
        ])->build()->getField();

        $this->changeset->setFieldValue(
            $list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $list_field)->build()
        );

        self::assertEquals(
            new StaticListFieldWithValue('static list field', DisplayType::BLOCK, []),
            $this->getField(new ConfiguredField($list_field, DisplayType::BLOCK)),
        );
    }

    public function testItBuildsValuesWithDecorators(): void
    {
        $list_field_value_red      = ListStaticValueBuilder::aStaticValue('Red')->withId(10002)->build();
        $list_field_value_no_color = ListStaticValueBuilder::aStaticValue('No color')->withId(10004)->build();

        $list_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(124)->inTracker($this->tracker)->withLabel('static list field with decorators')->build(),
        )->withBuildStaticValues([
            $list_field_value_red,
            $list_field_value_no_color,
        ])->withDecorators([
            $list_field_value_red->getId() => StaticBindDecoratorBuilder::withColor(ItemColor::fromName('red-wine'))->withFieldId(124)->withValueId($list_field_value_red->getId())->build(),
        ])->build()->getField();

        $this->changeset->setFieldValue(
            $list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $list_field)
                ->withValues([
                    $list_field_value_red,
                    $list_field_value_no_color,
                ])->build(),
        );

        self::assertEquals(
            new StaticListFieldWithValue('static list field with decorators', DisplayType::COLUMN, [
                new StaticListValue('Red', ItemColor::fromName('red-wine')),
                new StaticListValue('No color', null),
            ]),
            $this->getField(new ConfiguredField($list_field, DisplayType::COLUMN)),
        );
    }

    public function testItBuildsOpenListValues(): void
    {
        $open_list_custom_value = OpenListValueBuilder::anOpenListValue('Custom value')->build();
        $open_list_field        = ListStaticBindBuilder::aStaticBind(
            OpenListFieldBuilder::anOpenListField()->withId(125)->withLabel('static open list field')->build()
        )->withBuildStaticValues([$open_list_custom_value])->build()->getField();

        $this->changeset->setFieldValue(
            $open_list_field,
            ChangesetValueOpenListBuilder::aListOfValue(685, $this->changeset, $open_list_field)->withValues([$open_list_custom_value])->build(),
        );

        self::assertEquals(
            new StaticListFieldWithValue('static open list field', DisplayType::COLUMN, [
                new StaticListValue('Custom value', null),
            ]),
            $this->getField(new ConfiguredField($open_list_field, DisplayType::COLUMN)),
        );
    }

    private function getField(ConfiguredField $configured_field): StaticListFieldWithValue
    {
        $changeset_value = $this->changeset->getValue($configured_field->field);
        assert($changeset_value instanceof \Tracker_Artifact_ChangesetValue_List);

        return (new StaticListFieldWithValueBuilder())->buildStaticListFieldWithValue($configured_field, $changeset_value);
    }
}
