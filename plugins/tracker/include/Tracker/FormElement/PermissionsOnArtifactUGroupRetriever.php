<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tracker_FormElement_Field_PermissionsOnArtifact;

class PermissionsOnArtifactUGroupRetriever
{
    /**
     * @param array $value
     *
     * @return array
     */
    public function initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition(
        array $value,
        Tracker_FormElement_Field_PermissionsOnArtifact $field
    ) {
        if ($field->isRequired() === false) {
            $value['u_groups'] = [];
        }

        return $value;
    }

    /**
     * @return array
     */
    public function initializeUGroupsIfNoUGroupsAreChoosen(
        $value
    ) {
        if (is_array($value) === false) {
            $value = ['u_groups' => []];
        }

        return $value;
    }
}
