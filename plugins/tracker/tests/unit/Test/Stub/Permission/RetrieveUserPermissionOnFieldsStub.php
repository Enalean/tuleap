<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Permission;

use PFUser;
use Tracker_FormElement;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Permission\UserPermissionsOnItems;

final class RetrieveUserPermissionOnFieldsStub implements RetrieveUserPermissionOnFields
{
    /**
     * @var array<string, list<int>>
     */
    private array $has_permission_on = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @param list<int> $fields_id
     */
    public function withPermissionOn(array $fields_id, FieldPermissionType $permission): self
    {
        $this->has_permission_on[$permission->value] = $fields_id;

        return $this;
    }

    #[\Override]
    public function retrieveUserPermissionOnFields(PFUser $user, array $fields, FieldPermissionType $permission): UserPermissionsOnItems
    {
        if (isset($this->has_permission_on[$permission->value])) {
            return new UserPermissionsOnItems(
                $user,
                $permission,
                array_filter($fields, fn(Tracker_FormElement $field) => in_array($field->getId(), $this->has_permission_on[$permission->value])),
                array_filter($fields, fn(Tracker_FormElement $field) => ! in_array($field->getId(), $this->has_permission_on[$permission->value])),
            );
        }

        return new UserPermissionsOnItems($user, $permission, [], $fields);
    }
}
