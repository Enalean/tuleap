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

namespace Tuleap\Tracker\REST;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FrozenFieldDetector&MockObject $frozen_field_detector;

    private PermissionsExporter $permissions_exporter;

    protected function setUp(): void
    {
        $this->frozen_field_detector = $this->createMock(FrozenFieldDetector::class);
        $this->permissions_exporter  = new PermissionsExporter(
            $this->frozen_field_detector
        );
    }

    public function testItDoesNotComputePermissionsIfFormElementIsNotField()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = $this->createMock(Tracker_FormElement::class);
        $form_element
            ->method('exportCurrentUserPermissionsToREST')
            ->willReturn($initial_permissions);

        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->frozen_field_detector->expects($this->never())->method('isFieldFrozen');

        $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );
    }

    public function testItDoesNotChangePermissionsIfNotFrozenField()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = $this->createMock(\Tracker_FormElement_Field::class);
        $form_element
            ->method('exportCurrentUserPermissionsToREST')
            ->willReturn($initial_permissions);

        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->willReturn(false);

        $computed_permissions = $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );

        $this->assertCount(0, array_diff($initial_permissions, $computed_permissions));
    }

    public function testItShouldRemoveTheUpdatePermissionIfFieldIsFrozen()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = $this->createMock(\Tracker_FormElement_Field::class);
        $form_element
            ->method('exportCurrentUserPermissionsToREST')
            ->willReturn($initial_permissions);

        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->willReturn(true);

        $computed_permissions = $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );

        $this->assertEquals(1, count($computed_permissions));
        $this->assertContains(Tracker_FormElement::REST_PERMISSION_READ, $computed_permissions);
        $this->assertNotContains(Tracker_FormElement::REST_PERMISSION_UPDATE, $computed_permissions);
    }

    public function testItShouldOutputAnArrayThatDoesNotBecomeAJSONObject()
    {
        $initial_permissions = [
            Tracker_FormElement::REST_PERMISSION_READ,
            Tracker_FormElement::REST_PERMISSION_UPDATE,
            Tracker_FormElement::REST_PERMISSION_SUBMIT,
        ];
        $form_element        = $this->createMock(\Tracker_FormElement_Field::class);
        $form_element
            ->method('exportCurrentUserPermissionsToREST')
            ->willReturn($initial_permissions);

        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->frozen_field_detector->method('isFieldFrozen')->willReturn(true);

        $computed_permissions = $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );

        $expected_json = '["read","submit"]';
        self::assertSame($expected_json, json_encode($computed_permissions));
    }
}
