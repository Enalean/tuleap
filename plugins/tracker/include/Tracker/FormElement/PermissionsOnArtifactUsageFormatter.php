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

use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

class PermissionsOnArtifactUsageFormatter
{
    /**
     * @var PermissionsOnArtifactValidator
     */
    private $permissions_validator;

    public function __construct(PermissionsOnArtifactValidator $permissions_validator)
    {
        $this->permissions_validator = $permissions_validator;
    }

    /**
     * @return array
     */
    public function setRestrictAccessForArtifact(array $value)
    {
        if (empty($value) || $this->permissions_validator->isArtifactPermissionChecked($value) === false) {
            $value[PermissionsOnArtifactField::USE_IT] = 0;
        }

        return $value;
    }

    /**
     * @return array
     */
    public function alwaysUseRestrictedPermissionsForRequiredField(
        array $value,
        PermissionsOnArtifactField $field,
    ) {
        if ($field->isRequired() === true) {
            $value[PermissionsOnArtifactField::USE_IT] = 1;
        }

        return $value;
    }
}
