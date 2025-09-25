<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

use Tracker_Artifact_Changeset;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ComputedFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueComputedTest extends TestCase
{
    private Tracker_Artifact_Changeset $changeset;
    private ComputedField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field     = ComputedFieldBuilder::aComputedField(412)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(5)->build();
    }

    public function testNoDiff(): void
    {
        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 456.789, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 456.789, true);
        self::assertFalse($float_1->diff($float_2));
        self::assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        self::assertFalse($float_1->diff($float_2));
        self::assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, true);
        self::assertFalse($float_1->diff($float_2));
        self::assertFalse($float_2->diff($float_1));
    }

    public function testDiff(): void
    {
        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.321, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987, true);

        self::assertEquals('changed from 987 to 987.321', $float_1->diff($float_2));
        self::assertEquals('changed from 987.321 to 987', $float_2->diff($float_1));

        $float_5 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.4321, true);
        $float_6 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.4329, true);
        self::assertEquals('changed from 987.4329 to 987.4321', $float_5->diff($float_6));
        self::assertEquals('changed from 987.4321 to 987.4329', $float_6->diff($float_5));

        $float_7 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        $float_8 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 1233, true);
        self::assertEquals('changed from 1233 to 0', $float_7->diff($float_8));
        self::assertEquals('changed from 0 to 1233', $float_8->diff($float_7));

        $float_9  = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, false);
        $float_10 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 1233, true);
        self::assertEquals('changed from 1233 to autocomputed', $float_9->diff($float_10));
        self::assertEquals('changed from autocomputed to 1233', $float_10->diff($float_9));
    }
}
