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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Permission\Fields\ByGroup;

class ByGroupFieldsPermissionsForOtherGroups
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $permissions;

    public function __construct(int $id, string $name, array $permissions)
    {
        $this->id          = $id;
        $this->name        = $name;
        $permission_text = [
            \Tracker_FormElement::PERMISSION_READ   => dgettext('tuleap-tracker', 'Read only'),
            \Tracker_FormElement::PERMISSION_SUBMIT => dgettext('tuleap-tracker', 'Submit'),
            \Tracker_FormElement::PERMISSION_UPDATE => dgettext('tuleap-tracker', 'Update'),
        ];
        $this->permissions = implode(
            ', ',
            array_map(
                function ($perm) use ($permission_text) {
                    return $permission_text[$perm];
                },
                array_keys($permissions)
            )
        );
    }
}
