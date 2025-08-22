<?php
/**
 * Copyright (c) Enalean, 2011 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\List;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use ReflectionClass;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class CheckboxFieldTest extends TestCase
{
    public function testItIsNoneWhenArrayIsFullOfZero(): void
    {
        $field = $this->getCheckboxField();
        self::assertTrue($field->isNone(['0', '0', '0']));
    }

    public function testItIsNotNoneWhenArrayContainsAValue(): void
    {
        $field = $this->getCheckboxField();
        self::assertFalse($field->isNone(['1' => '0', '2' => '53']));
    }

    public function testItHasNoChangesWhenSubmittedValuesAreTheSameAsStored(): void
    {
        $field    = $this->getCheckboxField();
        $previous = $this->getPreviousChangesetValue($field);
        self::assertFalse($field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $previous, ['5123', '5125']));
    }

    public function testItHasNoChangesWhenSubmittedValuesContainsZero(): void
    {
        $field    = $this->getCheckboxField();
        $previous = $this->getPreviousChangesetValue($field);
        self::assertFalse($field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $previous, ['5123', '0', '5125']));
    }

    public function testItDetectsChangesEvenWhenCSVImportValueIsNull(): void
    {
        $field    = $this->getCheckboxField();
        $previous = $this->getPreviousChangesetValue($field);
        self::assertTrue($field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $previous, null));
    }

    public function testItHasChangesWhenSubmittedValuesContainsDifferentValues(): void
    {
        $field    = $this->getCheckboxField();
        $previous = $this->getPreviousChangesetValue($field);
        self::assertTrue($field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $previous, ['5123', '0', '5122']));
    }

    public function testItHasAnHiddenFieldForEachCheckbox(): void
    {
        $value      = ListStaticValueBuilder::aStaticValue('static')->withId(1)->build();
        $parameters = [$value, 'lename', false];

        $field = ListStaticBindBuilder::aStaticBind($this->getCheckboxField())->build()->getField();

        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('fetchFieldValue');
        $method->setAccessible(true);

        $html = $method->invokeArgs($field, $parameters);

        self::assertMatchesRegularExpression('/<input type="hidden" lename/', $html);
    }

    public function testItPresentsReadOnlyViewAsAList(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $bind     = ListStaticBindBuilder::aStaticBind($this->getCheckboxField())->withStaticValues([
            523 => 'Value_1',
            524 => 'Value_2',
            525 => 'Value_3',
        ])->build();
        $field    = $bind->getField();
        $value    = ChangesetValueListTestBuilder::aListOfValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $field)
            ->withValues($bind->getBindValues([523, 525]))->build();

        $html = $field->fetchArtifactValueReadOnly($artifact, $value);

        self::assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[x]</span> Value_1</li>', $html);
        self::assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[ ]</span> Value_2</li>', $html);
        self::assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[x]</span> Value_3</li>', $html);
    }

    public function testItReplaceCSVNullValueByNone(): void
    {
        $field = $this->getCheckboxField();
        self::assertEquals(
            [Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID],
            $field->getFieldDataFromCSVValue(null)
        );
    }

    public function testAcceptValueOfNonSelectedCheckbox(): void
    {
        $field = $this->getCheckboxField();
        self::assertTrue($field->checkValueExists('0'));
    }

    protected function getCheckboxField(): CheckboxField
    {
        return new CheckboxField(
            1,
            10,
            100,
            'checkbox',
            'checkbox label',
            'description',
            true,
            '',
            true,
            false,
            1
        );
    }

    protected function getPreviousChangesetValue(ListField $field): Tracker_Artifact_ChangesetValue_List
    {
        return ChangesetValueListTestBuilder::aListOfValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $field)
            ->withValues([
                ListStaticValueBuilder::aStaticValue('foo')->withId(5123)->build(),
                ListStaticValueBuilder::aStaticValue('bar')->withId(5125)->build(),
            ])
            ->build();
    }
}
