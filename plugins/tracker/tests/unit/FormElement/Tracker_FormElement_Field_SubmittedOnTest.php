<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedOnFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_SubmittedOnTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testhasChanges(): void
    {
        $field = SubmittedOnFieldBuilder::aSubmittedOnField(456)->build();
        $value = ChangesetValueDateTestBuilder::aValue(12, ChangesetTestBuilder::aChangeset(65)->build(), $field)->build();
        self::assertFalse($field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $value, null));
    }

    public function testisValid(): void
    {
        $field    = SubmittedOnFieldBuilder::aSubmittedOnField(456)->build();
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue($field->isValid($artifact, null));
    }
}
