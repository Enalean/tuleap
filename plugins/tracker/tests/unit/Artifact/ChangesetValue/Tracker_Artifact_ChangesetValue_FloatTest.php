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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Float;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetValue_FloatTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_Artifact_Changeset $changeset;
    private TrackerField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field     = FloatFieldBuilder::aFloatField(45)->withName('field_float')->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1)->build();
    }

    public function testFloats(): void
    {
        $float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 1.1234);
        self::assertEquals(1.1234, $float->getFloat());
        self::assertNotSame('1.1234', $float->getFloat());
        self::assertSame('1.1234', $float->getValue());

        $long_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 9.54321);
        self::assertEquals(9.54321, $long_float->getFloat());
        self::assertNotSame('9.54321', $long_float->getFloat());
        self::assertSame('9.5432', $long_float->getValue());

        $int_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 42);
        self::assertEquals(42, $int_float->getFloat());
        self::assertEquals(42.000, $int_float->getFloat());
        self::assertSame(42.000, $int_float->getFloat());
        self::assertNotSame('42', $int_float->getFloat());
        self::assertEquals('42', $int_float->getValue());

        $string_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, '123.456');
        self::assertEquals(123.456, $string_float->getFloat());
        self::assertNotEquals(123.457, $string_float->getFloat());
        self::assertEquals('123.456', $string_float->getFloat());
        self::assertNotSame('123.456', $string_float->getFloat());
        self::assertEquals('123.456', $string_float->getValue());

        $zero_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 0);
        self::assertEquals(0, $zero_float->getFloat());
        self::assertEquals('0', $zero_float->getValue());

        $null_float = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, null);
        self::assertNull($null_float->getFloat());
        self::assertNull($null_float->getValue());
    }

    public function testNoDiff(): void
    {
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.789);
        self::assertFalse($float_1->diff($float_2));
        self::assertFalse($float_2->diff($float_1));
    }

    public function testDiff(): void
    {
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.321);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987);

        self::assertEquals('changed from 987 to 987.321', $float_1->diff($float_2));
        self::assertEquals('changed from 987.321 to 987', $float_2->diff($float_1));

        $float_3 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54321);
        $float_4 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 456.54322);
        self::assertFalse($float_3->diff($float_4));
        self::assertFalse($float_4->diff($float_3));

        $float_5 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4321);
        $float_6 = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, false, 987.4329);
        self::assertEquals('changed from 987.4329 to 987.4321', $float_5->diff($float_6));
        self::assertEquals('changed from 987.4321 to 987.4329', $float_6->diff($float_5));

        $float_7 = new Tracker_Artifact_ChangesetValue_Float(456, $this->changeset, $this->field, false, 1);
        $float_8 = new Tracker_Artifact_ChangesetValue_Float(789, $this->changeset, $this->field, false, null);
        self::assertEquals('set to 1', $float_7->diff($float_8));
        self::assertEquals('cleared', $float_8->diff($float_7));
    }

    public function testTheRESTValueIsReturned(): void
    {
        $changeset_value = new Tracker_Artifact_ChangesetValue_Float(111, $this->changeset, $this->field, true, 45.1046);
        $representation  = $changeset_value->getRESTValue(UserTestBuilder::buildWithDefaults());

        self::assertEquals(45.1046, $representation->value);
    }
}
