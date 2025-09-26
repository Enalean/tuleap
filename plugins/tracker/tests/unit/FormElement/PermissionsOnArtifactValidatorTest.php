<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

#[DisableReturnValueGenerationForTestDoubles]
final class PermissionsOnArtifactValidatorTest extends TestCase
{
    private PermissionsOnArtifactField&MockObject $field;
    private PermissionsOnArtifactValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->field     = $this->createMock(PermissionsOnArtifactField::class);
        $this->validator = new PermissionsOnArtifactValidator();
    }

    public function testItReturnsFalseNoUgroupsSet(): void
    {
        $value = [];

        self::assertFalse($this->validator->hasAGroupSelected($value));
    }

    public function testItReturnsTrueWhenUgroupsSet(): void
    {
        $this->field->method('isRequired')->willReturn(true);
        $value['u_groups'] = [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED];

        self::assertTrue($this->validator->hasAGroupSelected($value));
    }

    public function testItReturnsTrueWhenNoneIsSelected(): void
    {
        $value['u_groups'] = [ProjectUGroup::NONE];

        self::assertTrue($this->validator->isNoneGroupSelected($value));
    }

    public function testItReturnsFalseWhenPermissionsAreNotSent(): void
    {
        $value = [];

        self::assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function testItReturnsFalseWhenPermissionsAreNotChecked(): void
    {
        $value = [
            PermissionsOnArtifactField::USE_IT => 0,
        ];

        self::assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function testItReturnsTrueWhenPermissionsAreSentAndChecked(): void
    {
        $value = [
            PermissionsOnArtifactField::USE_IT => 1,
        ];

        self::assertTrue($this->validator->isArtifactPermissionChecked($value));
    }
}
