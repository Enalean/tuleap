<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\FormElement\Field\List;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class RadioButtonFieldTest extends TestCase
{
    private function getField(): RadioButtonField
    {
        return new RadioButtonField(
            1147,
            111,
            1,
            'name',
            'label',
            'description',
            true,
            'S',
            false,
            false,
            1,
        );
    }

    public function testItIsNotNoneWhenArrayContainsAValue(): void
    {
        $field = $this->getField();
        self::assertFalse($field->isNone(['1' => '555']));
    }

    public function testItHasNoChangesWhenSubmittedValuesAreTheSameAsStored(): void
    {
        $field    = $this->getField();
        $previous = ChangesetValueListTestBuilder::aListOfValue(12, ChangesetTestBuilder::aChangeset(98)->build(), $field)
            ->withValues([ListStaticValueBuilder::aStaticValue('value')->withId(5123)->build()])
            ->build();
        self::assertFalse($field->hasChanges(ArtifactTestBuilder::anArtifact(654)->build(), $previous, ['5123']));
    }

    public function testItDetectsChangesEvenWhenCSVImportValueIsNull(): void
    {
        $field    = $this->getField();
        $previous = ChangesetValueListTestBuilder::aListOfValue(12, ChangesetTestBuilder::aChangeset(98)->build(), $field)
            ->withValues([ListStaticValueBuilder::aStaticValue('value')->withId(5123)->build()])
            ->build();
        self::assertTrue($field->hasChanges(ArtifactTestBuilder::anArtifact(654)->build(), $previous, null));
    }

    public function testItHasChangesWhenSubmittedValuesContainsDifferentValues(): void
    {
        $field    = $this->getField();
        $previous = ChangesetValueListTestBuilder::aListOfValue(12, ChangesetTestBuilder::aChangeset(98)->build(), $field)
            ->withValues([ListStaticValueBuilder::aStaticValue('value')->withId(5123)->build()])
            ->build();
        self::assertTrue($field->hasChanges(ArtifactTestBuilder::anArtifact(654)->build(), $previous, ['5122']));
    }

    public function testItReplaceCSVNullValueByNone(): void
    {
        $field = $this->getField();
        self::assertEquals(
            Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            $field->getFieldDataFromCSVValue(null, null)
        );
    }
}
