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

use PFUser;
use Tracker_Artifact;
use Tracker_FormElement;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class PermissionsExporter
{
    public const READ_ONLY_PRESERVED_PERMISSIONS = [
        Tracker_FormElement::REST_PERMISSION_READ,
        Tracker_FormElement::REST_PERMISSION_SUBMIT
    ];

    /**
     * @var FrozenFieldDetector
     */
    private $frozen_field_detector;

    public function __construct(FrozenFieldDetector $read_only_field_detector)
    {
        $this->frozen_field_detector = $read_only_field_detector;
    }

    public function exportUserPermissionsForFieldWithoutWorkflowComputedPermissions(
        PFUser $user,
        Tracker_FormElement $field
    ): array {
        return $field->exportCurrentUserPermissionsToREST($user);
    }

    public function exportUserPermissionsForFieldWithWorkflowComputedPermissions(
        PFUser $user,
        Tracker_FormElement $field,
        Tracker_Artifact $artifact
    ): array {
        $permissions = $this->exportUserPermissionsForFieldWithoutWorkflowComputedPermissions($user, $field);

        if (! $field instanceof Tracker_FormElement_Field) {
            return $permissions;
        }

        if ($this->frozen_field_detector->isFieldFrozen($artifact, $field)) {
            $permissions = $this->removeUpdatePermissionFromField($permissions);
        }

        return $permissions;
    }

    private function removeUpdatePermissionFromField(array $permissions): array
    {
        return array_values(array_filter($permissions, static function (string $permission): bool {
            return in_array($permission, PermissionsExporter::READ_ONLY_PRESERVED_PERMISSIONS);
        }));
    }
}
