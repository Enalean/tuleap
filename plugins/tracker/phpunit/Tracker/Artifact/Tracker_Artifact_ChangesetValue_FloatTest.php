<?php
/**
 * Copyright (c) Enalean, 2015-2019. All Rights Reserved.
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
use Tuleap\GlobalLanguageMock;

final class Tracker_Artifact_ChangesetValue_FloatTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    private $changeset;
    private $field;

    protected function setUp() : void
    {
        $this->field     = \Mockery::mock(Tracker_FormElement_Field_Float::class);
        $this->changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
    }

    public function testFloats() : void
    {
        $float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 1.1234);
        $this->assertEquals($float->getFloat(), 1.1234);
        $this->assertNotSame($float->getFloat(), '1.1234');
        $this->assertSame($float->getValue(), '1.1234');

        $long_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 9.54321);
        $this->assertEquals($long_float->getFloat(), 9.54321);
        $this->assertNotSame($long_float->getFloat(), '9.54321');
        $this->assertSame($long_float->getValue(), '9.5432');

        $int_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 42);
        $this->assertEquals($int_float->getFloat(), 42);
        $this->assertEquals($int_float->getFloat(), 42.000);
        $this->assertSame($int_float->getFloat(), 42.000);
        $this->assertNotSame($int_float->getFloat(), '42');
        $this->assertEquals($int_float->getValue(), '42.0000');

        $string_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, '123.456');
        $this->assertEquals($string_float->getFloat(), 123.456);
        $this->assertNotEquals($string_float->getFloat(), 123.457);
        $this->assertEquals($string_float->getFloat(), '123.456');
        $this->assertNotSame($string_float->getFloat(), '123.456');
        $this->assertEquals($string_float->getValue(), '123.4560');

        $null_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, null);
        $this->assertNull($null_float->getFloat());
        $this->assertSame($null_float->getValue(), '');
    }

    public function testNoDiff() : void
    {
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));
    }

    public function testDiff() : void
    {
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_artifact', 'changed_from')->andReturns('changed from');
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_artifact', 'to')->andReturns('to');

        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.321);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987);

        $this->assertEquals($float_1->diff($float_2), 'changed from 987.0000 to 987.3210');
        $this->assertEquals($float_2->diff($float_1), 'changed from 987.3210 to 987.0000');

        $float_3 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54321);
        $float_4 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54322);
        $this->assertFalse($float_3->diff($float_4));
        $this->assertFalse($float_4->diff($float_3));

        $float_5 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4321);
        $float_6 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4329);
        $this->assertEquals($float_5->diff($float_6), 'changed from 987.4329 to 987.4321');
        $this->assertEquals($float_6->diff($float_5), 'changed from 987.4321 to 987.4329');
    }

    public function testTheRESTValueIsReturned() : void
    {
        $this->field->shouldReceive('getId')->andReturns('45');
        $this->field->shouldReceive('getLabel')->andReturns('field_float');

        $changeset_value = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, true, 45.1046);
        $representation = $changeset_value->getRESTValue(\Mockery::mock(\PFUser::class));

        $this->assertEquals($representation->value, 45.1046);
    }
}
