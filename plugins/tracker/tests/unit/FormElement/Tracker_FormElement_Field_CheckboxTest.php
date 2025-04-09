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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_CheckboxTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItIsNoneWhenArrayIsFullOfZero(): void
    {
        $field = $this->getCheckboxField();
        $this->assertTrue($field->isNone(['0', '0', '0']));
    }

    public function testItIsNotNoneWhenArrayContainsAValue(): void
    {
        $field = $this->getCheckboxField();
        $this->assertFalse($field->isNone(['1' => '0', '2' => '53']));
    }

    public function testItHasNoChangesWhenSubmittedValuesAreTheSameAsStored(): void
    {
        $previous = $this->getPreviousCHangesetValue();
        $field    = $this->getCheckboxField();
        $this->assertFalse($field->hasChanges(Mockery::mock(Artifact::class), $previous, ['5123', '5125']));
    }

    public function testItHasNoChangesWhenSubmittedValuesContainsZero(): void
    {
        $previous = $this->getPreviousCHangesetValue();
        $field    = $this->getCheckboxField();
        $this->assertFalse($field->hasChanges(Mockery::mock(Artifact::class), $previous, ['5123', '0', '5125']));
    }

    public function testItDetectsChangesEvenWhenCSVImportValueIsNull(): void
    {
        $previous = $this->getPreviousCHangesetValue();
        $field    = $this->getCheckboxField();
        $this->assertTrue($field->hasChanges(Mockery::mock(Artifact::class), $previous, null));
    }

    public function testItHasChangesWhenSubmittedValuesContainsDifferentValues(): void
    {
        $previous = $this->getPreviousCHangesetValue();
        $field    = $this->getCheckboxField();
        $this->assertTrue($field->hasChanges(Mockery::mock(Artifact::class), $previous, ['5123', '0', '5122']));
    }

    public function testItHasAnHiddenFieldForEachCheckbox(): void
    {
        $value      = ListStaticValueBuilder::aStaticValue('static')->withId(1)->build();
        $parameters = [$value, 'lename', false];

        $field = $this->getCheckboxField();
        $bind  = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive('formatChangesetValueWithoutLink')->once();
        $field->setBind($bind);

        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('fetchFieldValue');
        $method->setAccessible(true);

        $html = $method->invokeArgs($field, $parameters);

        $this->assertMatchesRegularExpression('/<input type="hidden" lename/', $html);
    }

    public function testItPresentsReadOnlyViewAsAList(): void
    {
        $artifact     = Mockery::mock(Artifact::class);
        $value        = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $bind         = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_value   = ListStaticValueBuilder::aStaticValue('static')->withId(523)->build();
        $bind_value_2 = ListStaticValueBuilder::aStaticValue('static')->withId(524)->build();
        $bind_value_3 = ListStaticValueBuilder::aStaticValue('static')->withId(525)->build();
        $bind->shouldReceive('getAllVisibleValues')->andReturn([$bind_value->getId() => $bind_value, $bind_value_2->getId() => $bind_value_2, $bind_value_3->getId() => $bind_value_3]);
        $value->shouldReceive('getListValues')->andReturn([$bind_value->getId() => $bind_value, $bind_value_3->getId() => $bind_value_3]);

        $bind->shouldReceive('formatChangesetValueWithoutLink')->with($bind_value)->andReturn('Value_1');
        $bind->shouldReceive('formatChangesetValueWithoutLink')->with($bind_value_2)->andReturn('Value_2');
        $bind->shouldReceive('formatChangesetValueWithoutLink')->with($bind_value_3)->andReturn('Value_3');

        $field = $this->getCheckboxField();
        $field->setBind($bind);
        $html = $field->fetchArtifactValueReadOnly($artifact, $value);

        $this->assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[x]</span> Value_1</li>', $html);
        $this->assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[ ]</span> Value_2</li>', $html);
        $this->assertStringContainsString('<li><span class="tracker-read-only-checkbox-list-item">[x]</span> Value_3</li>', $html);
    }

    public function testItReplaceCSVNullValueByNone(): void
    {
        $field = $this->getCheckboxField();
        $this->assertEquals(
            [Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID],
            $field->getFieldDataFromCSVValue(null, null)
        );
    }

    public function testAcceptValueOfNonSelectedCheckbox(): void
    {
        $field = $this->getCheckboxField();
        self::assertTrue($field->checkValueExists('0'));
    }

    protected function getCheckboxField(): Tracker_FormElement_Field_Checkbox
    {
        return new Tracker_FormElement_Field_Checkbox(
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

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetValue_List
     */
    protected function getPreviousCHangesetValue()
    {
        $previous = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $previous->shouldReceive('getValue')->andReturn([5123, 5125]);

        return $previous;
    }
}
