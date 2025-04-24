<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\FloatingPointNumber;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetValue_Float;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class ChangesCheckerTest extends TestCase
{
    private Tracker_Artifact_ChangesetValue_Float&MockObject $old_value;
    private ChangesChecker $checker;

    protected function setUp(): void
    {
        $this->old_value = $this->createMock(Tracker_Artifact_ChangesetValue_Float::class);
        $this->checker   = new ChangesChecker();
    }

    public function testChecksIfChangesOccuredAtArtifactUpdate(): void
    {
        $this->old_value->method('getNumeric')->willReturn(1.1);

        self::assertTrue($this->checker->hasChanges($this->old_value, 2.0));
        self::assertFalse($this->checker->hasChanges($this->old_value, 1.1));
    }

    public function testShouldBeTrueIfPreviousValueWasNullAndNewValueIsZero(): void
    {
        $this->old_value->method('getNumeric')->willReturn(null);
        $new_value = 0;

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeTrueIfOldValueIsZeroAndNewValueIsNull(): void
    {
        $this->old_value->method('getNumeric')->willReturn(0.0);
        $new_value = null;

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeTrueIfOldValueIsZeroAndNewValueIsEmpty(): void
    {
        $this->old_value->method('getNumeric')->willReturn(0.0);
        $new_value = '';

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnNullValue(): void
    {
        $this->old_value->method('getNumeric')->willReturn(null);
        $new_value = null;

        self::assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdatingNullValueToEmpty(): void
    {
        $this->old_value->method('getNumeric')->willReturn(null);
        $new_value = '';

        self::assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnZeroValue(): void
    {
        $this->old_value->method('getNumeric')->willReturn(0.0);
        $new_value = 0;

        self::assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }
}
