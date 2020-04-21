<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

final class Tracker_FormElement_Field_Numeric_GetComputedValueTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItDelegatesRetrievalOfTheOldValueToTheDaoWhenNoTimestampGiven(): void
    {
        $user      = new PFUser(['language_id' => 'en']);
        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_FloatDao::class)
            ->shouldReceive('getLastValue')->andReturns(['value' => '123.45'])->getMock();
        $artifact  = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);
        $field     = \Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $actual_value = $field->getComputedValue($user, $artifact);
        $this->assertEquals('123.45', $actual_value);
    }

    public function testItDelegatesRetrievalOfTheOldValueToTheDaoWhenGivenATimestamp(): void
    {
        $artifact_id    = 123;
        $field_id       = 195;
        $user           = new PFUser(['language_id' => 'en']);
        $value_dao      = \Mockery::mock(\Tracker_FormElement_Field_Value_FloatDao::class);
        $artifact       =  Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($artifact_id);
        $field          = \Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $timestamp      = 9340590569;
        $value          = 67.89;

        $field->shouldReceive('getId')->andReturns($field_id);
        $field->shouldReceive('getValueDao')->andReturns($value_dao);
        $field->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $value_dao->shouldReceive('getValueAt')->with($artifact_id, $field_id, $timestamp)->andReturns(
            ['value' => $value]
        );

        $this->assertSame($value, $field->getComputedValue($user, $artifact, $timestamp));
    }

    public function testItReturnsZeroWhenUserDoesntHavePermissions(): void
    {
        $user           = new PFUser(['language_id' => 'en']);
        $artifact       =  Mockery::mock(Tracker_Artifact::class);
        $field          = \Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('userCanRead')->with($user)->andReturns(false);

        $actual_value = $field->getComputedValue($user, $artifact);
        $this->assertEquals(0, $actual_value);
    }
}
