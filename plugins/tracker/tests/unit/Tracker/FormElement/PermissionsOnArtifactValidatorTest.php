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

namespace Tuleap\Tracker\FormElement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker_FormElement_Field_PermissionsOnArtifact;

final class PermissionsOnArtifactValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_FormElement_Field_PermissionsOnArtifact */
    private $field;

    /** @var PermissionsOnArtifactValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->field     = \Mockery::spy(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $this->validator = new PermissionsOnArtifactValidator();
    }

    public function testItReturnsFalseNoUgroupsSet(): void
    {
        $value = [];

        $this->assertFalse(
            $this->validator->hasAGroupSelected($value)
        );
    }

    public function testItReturnsTrueWhenUgroupsSet(): void
    {
        $this->field->shouldReceive('isRequired')->andReturns(true);
        $value['u_groups'] = [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED];

        $this->assertTrue(
            $this->validator->hasAGroupSelected($value)
        );
    }

    public function testItReturnsTrueWhenNoneIsSelected(): void
    {
        $value['u_groups'] = [ProjectUGroup::NONE];

        $this->assertTrue(
            $this->validator->isNoneGroupSelected($value)
        );
    }

    public function testItReturnsFalseWhenPermissionsAreNotSent(): void
    {
        $value = [];

        $this->assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function testItReturnsFalseWhenPermissionsAreNotChecked(): void
    {
        $value = [
            Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT => 0
        ];

        $this->assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function testItReturnsTrueWhenPermissionsAreSentAndChecked(): void
    {
        $value = [
            Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT => 1
        ];

        $this->assertTrue($this->validator->isArtifactPermissionChecked($value));
    }
}
