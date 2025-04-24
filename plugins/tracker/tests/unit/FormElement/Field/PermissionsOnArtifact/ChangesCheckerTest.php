<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class ChangesCheckerTest extends TestCase
{
    private ChangesChecker $checker;
    private Tracker_Artifact_ChangesetValue_PermissionsOnArtifact&MockObject $old_value;

    protected function setUp(): void
    {
        $this->old_value = $this->createMock(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $this->checker   = new ChangesChecker();
    }

    public function testShouldBeTrueIfPermissionsOnArtifactAreNowUsed(): void
    {
        $this->old_value->method('getUsed')->willReturn('0');

        $new_values = [
            'use_artifact_permissions' => '1',
            'u_groups'                 => [3],
        ];

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfUgroupsSelectedForPermissionsOnArtifactChanged(): void
    {
        $this->old_value->method('getUsed')->willReturn('1');
        $this->old_value->method('getPerms')->willReturn([2]);

        $new_values = [
            'use_artifact_permissions' => '1',
            'u_groups'                 => [3],
        ];

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfNoUgroupsSelectedForPermissionsOnArtifactChanged(): void
    {
        $this->old_value->method('getUsed')->willReturn('1');
        $this->old_value->method('getPerms')->willReturn([2]);

        $new_values = [
            'use_artifact_permissions' => '1',
        ];

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfUgroupsSelectedForPermissionsOnArtifactChangedButNoUgroupInLastChangeset(): void
    {
        $this->old_value->method('getUsed')->willReturn('1');
        $this->old_value->method('getPerms')->willReturn([]);

        $new_values = [
            'use_artifact_permissions' => '1',
            'u_groups'                 => [3],
        ];

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeFalseIfNothingChanged(): void
    {
        $this->old_value->method('getUsed')->willReturn('1');
        $this->old_value->method('getPerms')->willReturn([3]);

        $new_values = [
            'use_artifact_permissions' => '1',
            'u_groups'                 => [3],
        ];

        self::assertFalse($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueWhenOldValuesAreOnNewValuesWithOtherNewValues(): void
    {
        $this->old_value->method('getUsed')->willReturn('1');
        $this->old_value->method('getPerms')->willReturn([3]);

        $new_values = [
            'use_artifact_permissions' => '1',
            'u_groups'                 => [3, 4],
        ];

        self::assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeFalseIfStillNotUsed(): void
    {
        $this->old_value->method('getUsed')->willReturn('0');
        $this->old_value->method('getPerms')->willReturn([]);

        $new_values = [
            'use_artifact_permissions' => '0',
        ];

        self::assertFalse($this->checker->hasChanges($this->old_value, $new_values));
    }
}
