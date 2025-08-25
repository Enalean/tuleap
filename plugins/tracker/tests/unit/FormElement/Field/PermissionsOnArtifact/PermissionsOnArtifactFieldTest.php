<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use ForgeAccess;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
class PermissionsOnArtifactFieldTest extends TestCase
{
    use GlobalResponseMock;

    private PermissionsOnArtifactField&MockObject $field;
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $this->field    = $this->createPartialMock(PermissionsOnArtifactField::class, ['isRequired']);
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];
        $this->field->getFieldDataFromRESTValueByField($value);
    }

    public function testItReturnsTrueWhenCheckboxIsCheckedAndAUgroupIsSelected(): void
    {
        $this->field->method('isRequired')->willReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => [ForgeAccess::ANONYMOUS],
        ];
        self::assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }

    public function testItReturnsTrueWhenCheckboxIsUnchecked(): void
    {
        $this->field->method('isRequired')->willReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => false,
        ];
        self::assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }

    public function testItReturnsTrueWhenArrayIsEmpty(): void
    {
        $this->field->method('isRequired')->willReturn(false);
        $submitted_values = [];
        self::assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }

    public function testItReturnsFalseWhenCheckboxIsCheckedAndNoUGroupIsSelected(): void
    {
        $this->field->method('isRequired')->willReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => [],
        ];

        self::assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }

    public function testItReturnsFalseWhenFieldIsRequiredAndNoValueAreSet(): void
    {
        $this->field->method('isRequired')->willReturn(true);
        $submitted_values = [];
        self::assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }

    public function testItReturnsFalseWhenFieldIsRequiredAndValueAreNotCorrectlySet(): void
    {
        $this->field->method('isRequired')->willReturn(true);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => [],
        ];
        self::assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus(
                $this->artifact,
                $submitted_values,
                UserTestBuilder::buildWithDefaults(),
            )
        );
    }
}
