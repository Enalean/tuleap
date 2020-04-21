<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Mockery;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Field_Integer;

final class Tracker_Artifact_ChangesetValue_IntegerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testIntegers(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $changest = Mockery::mock(Tracker_Artifact_Changeset::class);
        $integer = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, 42);
        $this->assertSame(42, $integer->getInteger());

        $string_int = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, '55');
        $this->assertSame(55, $string_int->getInteger());

        $null_int = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, null);
        $this->assertNull($null_int->getInteger());
        $this->assertNull($null_int->getValue());
    }

    public function testNoDiff(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $changest = Mockery::mock(Tracker_Artifact_Changeset::class);
        $int_1 = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, 54);
        $int_2 = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, 54);
        $this->assertFalse($int_1->diff($int_2));
        $this->assertFalse($int_2->diff($int_1));
    }

    public function testDiff(): void
    {
        $field    = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $changest = Mockery::mock(Tracker_Artifact_Changeset::class);
        $int_1    = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, 66);
        $int_2    = new Tracker_Artifact_ChangesetValue_Integer(111, $changest, $field, false, 666);
        $this->assertEquals('changed from 666 to 66', $int_1->diff($int_2));
    }

    public function testItReturnsTheRESTValue(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $field->shouldReceive('getId')->andReturn(10);
        $field->shouldReceive('getLabel')->andReturn("integer");

        $user  = Mockery::mock(PFUser::class);

        $changeset = new Tracker_Artifact_ChangesetValue_Integer(111, Mockery::mock(Tracker_Artifact_Changeset::class), $field, true, 556);
        $representation = $changeset->getRESTValue($user);

        $this->assertEquals(556, $representation->value);
    }
}
