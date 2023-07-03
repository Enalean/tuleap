<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Tracker\Artifact\Artifact;

final class CanPermissionsBeFullyMovedVerifier implements VerifyPermissionsCanBeFullyMoved
{
    public function canAllPermissionsBeFullyMoved(
        Tracker_FormElement_Field_PermissionsOnArtifact $source_field,
        Tracker_FormElement_Field_PermissionsOnArtifact $destination_field,
        Artifact $artifact,
    ): bool {
        $last_changeset_value = $source_field->getLastChangesetValue($artifact);
        if (! $last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_PermissionsOnArtifact) {
            return false;
        }

        $selected_user_groups    = $last_changeset_value->getUgroupNamesFromPerms();
        $destination_user_groups = array_map(static fn($user_group) => $user_group->getName(), $destination_field->getAllUserGroups());

        foreach ($selected_user_groups as $selected_user_group) {
            if (! in_array($selected_user_group, $destination_user_groups, true)) {
                return false;
            }
        }

        return true;
    }
}
