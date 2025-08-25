<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use ProjectUGroup;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

class PermissionsOnArtifactValidator
{
    /**
     *
     * @return bool
     */
    public function hasAGroupSelected(array $value)
    {
        return isset($value['u_groups']) === true;
    }

    /**
     *
     * @return bool
     */
    public function isNoneGroupSelected(array $value)
    {
        return isset($value['u_groups']) && in_array(ProjectUGroup::NONE, $value['u_groups']);
    }

    /**
     * @return bool
     */
    public function isArtifactPermissionChecked(array $value)
    {
        return (isset($value[PermissionsOnArtifactField::USE_IT])
            && $value[PermissionsOnArtifactField::USE_IT] == 1);
    }
}
