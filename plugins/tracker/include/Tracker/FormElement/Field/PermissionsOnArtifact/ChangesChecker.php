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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_FormElement_Field_PermissionsOnArtifact;

class ChangesChecker
{

    public function hasChanges(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $old_value, $new_value): bool
    {
        if ((bool) $old_value->getUsed() !== (bool) $new_value[Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT]) {
            return true;
        }

        if (
            (bool) $old_value->getUsed() === false &&
            (bool) $old_value->getUsed() === (bool) $new_value[Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT]
        ) {
            return false;
        }

        $ugroups_diff = array_diff($old_value->getPerms(), $new_value['u_groups']);
        return count($ugroups_diff) > 0;
    }
}
