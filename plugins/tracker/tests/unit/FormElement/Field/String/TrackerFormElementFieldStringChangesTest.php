<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\String;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field_String;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementFieldStringChangesTest extends TestCase
{
    private Tracker_Artifact_ChangesetValue_Text $previous_value;
    private Tracker_FormElement_Field_String $field;

    protected function setUp(): void
    {
        $this->field          = StringFieldBuilder::aStringField(1456)->build();
        $changeset            = ChangesetTestBuilder::aChangeset(654)->build();
        $this->previous_value = ChangesetValueStringTestBuilder::aValue(1, $changeset, $this->field)
            ->withValue('1')
            ->build();
    }

    public function testItReturnsTrueIfThereIsAChange(): void
    {
        $new_value = '1.0';

        self::assertTrue($this->field->hasChanges(ArtifactTestBuilder::anArtifact(6546)->build(), $this->previous_value, $new_value));
    }

    public function testItReturnsFalseIfThereIsNoChange(): void
    {
        $new_value = '1';

        self::assertFalse($this->field->hasChanges(ArtifactTestBuilder::anArtifact(6546)->build(), $this->previous_value, $new_value));
    }
}
