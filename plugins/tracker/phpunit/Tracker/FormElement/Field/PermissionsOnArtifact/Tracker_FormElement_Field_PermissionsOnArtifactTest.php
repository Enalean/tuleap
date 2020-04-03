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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use ForgeAccess;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

class Tracker_FormElement_Field_PermissionsOnArtifactTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    /**
     * @var Tracker_FormElement_Field_PermissionsOnArtifact
     */
    private $field;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    public function setUp(): void
    {
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->field    = Mockery::mock(\Tracker_FormElement_Field_PermissionsOnArtifact::class)
                                 ->makePartial()
                                 ->shouldAllowMockingProtectedMethods();
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];
        $this->field->getFieldDataFromRESTValueByField($value);
    }

    public function testItReturnsTrueWhenCheckboxIsCheckedAndAUgroupIsSelected()
    {
        $this->field->shouldReceive('isRequired')->andReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => [ForgeAccess::ANONYMOUS]
        ];
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function testItReturnsTrueWhenCheckboxIsUnchecked()
    {
        $this->field->shouldReceive('isRequired')->andReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => false
        ];
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function testItReturnsTrueWhenArrayIsEmpty()
    {
        $this->field->shouldReceive('isRequired')->andReturn(false);
        $submitted_values = [];
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function testItReturnsFalseWhenCheckboxIsCheckedAndNoUGroupIsSelected()
    {
        $this->field->shouldReceive('isRequired')->andReturn(false);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => []
        ];

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function testItReturnsFalseWhenFieldIsRequiredAndNoValueAreSet()
    {
        $this->field->shouldReceive('isRequired')->andReturn(true);
        $submitted_values = [];
        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function testItReturnsFalseWhenFieldIsRequiredAndValueAreNotCorrectlySet()
    {
        $this->field->shouldReceive('isRequired')->andReturn(true);
        $submitted_values = [
            'use_artifact_permissions' => true,
            'u_groups'                 => []
        ];
        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }
}
