<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Artifact;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_FormElement_Field_Float;

final class Tracker_Artifact_ChangesetValue_FloatTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    private $changeset;
    private $field;

    protected function setUp(): void
    {
        $this->field     = \Mockery::mock(Tracker_FormElement_Field_Float::class);
        $this->changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
    }

    public function testFloats(): void
    {
        $float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 1.1234);
        $this->assertEquals(1.1234, $float->getFloat());
        $this->assertNotSame('1.1234', $float->getFloat());
        $this->assertSame('1.1234', $float->getValue());

        $long_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 9.54321);
        $this->assertEquals(9.54321, $long_float->getFloat());
        $this->assertNotSame('9.54321', $long_float->getFloat());
        $this->assertSame('9.5432', $long_float->getValue());

        $int_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 42);
        $this->assertEquals(42, $int_float->getFloat());
        $this->assertEquals(42.000, $int_float->getFloat());
        $this->assertSame(42.000, $int_float->getFloat());
        $this->assertNotSame('42', $int_float->getFloat());
        $this->assertEquals('42', $int_float->getValue());

        $string_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, '123.456');
        $this->assertEquals(123.456, $string_float->getFloat());
        $this->assertNotEquals(123.457, $string_float->getFloat());
        $this->assertEquals('123.456', $string_float->getFloat());
        $this->assertNotSame('123.456', $string_float->getFloat());
        $this->assertEquals('123.456', $string_float->getValue());

        $zero_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 0);
        $this->assertEquals(0, $zero_float->getFloat());
        $this->assertEquals('0', $zero_float->getValue());

        $null_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, null);
        $this->assertNull($null_float->getFloat());
        $this->assertNull($null_float->getValue());
    }

    public function testNoDiff(): void
    {
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));
    }

    public function testDiff(): void
    {
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.321);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987);

        $this->assertEquals('changed from 987 to 987.321', $float_1->diff($float_2));
        $this->assertEquals('changed from 987.321 to 987', $float_2->diff($float_1));

        $float_3 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54321);
        $float_4 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54322);
        $this->assertFalse($float_3->diff($float_4));
        $this->assertFalse($float_4->diff($float_3));

        $float_5 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4321);
        $float_6 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4329);
        $this->assertEquals('changed from 987.4329 to 987.4321', $float_5->diff($float_6));
        $this->assertEquals('changed from 987.4321 to 987.4329', $float_6->diff($float_5));

        $float_7 = new Tracker_Artifact_ChangesetValue_Float(456, $this->changeset, $this->field, false, 1);
        $float_8 = new Tracker_Artifact_ChangesetValue_Float(789, $this->changeset, $this->field, false, null);
        $this->assertEquals('set to 1', $float_7->diff($float_8));
        $this->assertEquals('cleared', $float_8->diff($float_7));
    }

    public function testTheRESTValueIsReturned(): void
    {
        $this->field->shouldReceive('getId')->andReturns('45');
        $this->field->shouldReceive('getLabel')->andReturns('field_float');

        $changeset_value = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, true, 45.1046);
        $representation = $changeset_value->getRESTValue(\Mockery::mock(\PFUser::class));

        $this->assertEquals($representation->value, 45.1046);
    }
}
