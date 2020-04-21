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

final class Tracker_FormElement_Field_RadiobuttonTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItIsNotNoneWhenArrayContainsAValue(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Radiobutton::class)->makePartial();
        $this->assertFalse($field->isNone(['1' => '555']));
    }

    public function testItHasNoChangesWhenSubmittedValuesAreTheSameAsStored(): void
    {
        $previous = \Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getValue')->andReturns(
            [5123]
        )->getMock();
        $field    = Mockery::mock(Tracker_FormElement_Field_Radiobutton::class)->makePartial();
        $this->assertFalse($field->hasChanges(\Mockery::mock(\Tracker_Artifact::class), $previous, ['5123']));
    }

    public function testItDetectsChangesEvenWhenCSVImportValueIsNull(): void
    {
        $previous = \Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getValue')->andReturns(
            [5123]
        )->getMock();
        $field    = Mockery::mock(Tracker_FormElement_Field_Radiobutton::class)->makePartial();
        $this->assertTrue($field->hasChanges(\Mockery::mock(\Tracker_Artifact::class), $previous, null));
    }

    public function testItHasChangesWhenSubmittedValuesContainsDifferentValues(): void
    {
        $previous = \Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getValue')->andReturns(
            ['5123']
        )->getMock();
        $field    = Mockery::mock(Tracker_FormElement_Field_Radiobutton::class)->makePartial();
        $this->assertTrue($field->hasChanges(\Mockery::mock(\Tracker_Artifact::class), $previous, ['5122']));
    }

    public function testItReplaceCSVNullValueByNone(): void
    {
        $field = Mockery::spy(Tracker_FormElement_Field_Radiobutton::class)->makePartial();
        $this->assertEquals(
            Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            $field->getFieldDataFromCSVValue(null, null)
        );
    }
}
