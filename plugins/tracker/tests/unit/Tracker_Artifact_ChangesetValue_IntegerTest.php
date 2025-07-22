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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tracker_Artifact_ChangesetValue_Integer;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetValue_IntegerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testIntegers(): void
    {
        $field     = IntegerFieldBuilder::anIntField(306)->build();
        $changeset = ChangesetTestBuilder::aChangeset(164)->build();
        $integer   = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, 42);
        self::assertSame(42, $integer->getInteger());

        $string_int = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, '55');
        self::assertSame(55, $string_int->getInteger());

        $null_int = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, null);
        self::assertNull($null_int->getInteger());
        self::assertNull($null_int->getValue());
    }

    public function testNoDiff(): void
    {
        $field     = IntegerFieldBuilder::anIntField(306)->build();
        $changeset = ChangesetTestBuilder::aChangeset(164)->build();
        $int_1     = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, 54);
        $int_2     = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, 54);
        self::assertFalse($int_1->diff($int_2));
        self::assertFalse($int_2->diff($int_1));
    }

    public function testDiff(): void
    {
        $field     = IntegerFieldBuilder::anIntField(306)->build();
        $changeset = ChangesetTestBuilder::aChangeset(164)->build();
        $int_1     = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, 66);
        $int_2     = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, false, 666);
        self::assertEquals('changed from 666 to 66', $int_1->diff($int_2));
    }

    public function testItReturnsTheRESTValue(): void
    {
        $field     = IntegerFieldBuilder::anIntField(10)->withLabel('integer')->build();
        $changeset = ChangesetTestBuilder::aChangeset(164)->build();

        $value          = new Tracker_Artifact_ChangesetValue_Integer(111, $changeset, $field, true, 556);
        $representation = $value->getRESTValue(UserTestBuilder::buildWithDefaults());

        self::assertEquals(556, $representation->value);
    }
}
