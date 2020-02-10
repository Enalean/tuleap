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

class ByGroupFieldsPermissions
{
    /**
     * @var int
     */
    private $ugroup_id;
    /**
     * @var string
     */
    private $ugroup_name;

    private $fields = [];

    private $fields_permissions = [];
    /**
     * @var ByGroupUGroupListPresenter[]
     */
    private $ugroup_list;
    /**
     * @var bool
     */
    private $might_not_have_access;
    /**
     * @var array
     */
    private $permissions_for_other_groups;

    public function __construct(int $ugroup_id, string $ugroup_name)
    {
        $this->ugroup_id           = $ugroup_id;
        $this->ugroup_name         = $ugroup_name;
    }

    public function addField(\Tracker_FormElement_Field $field, array $permissions) : void
    {
        $this->fields[$field->getId()] = $field;
        $this->fields_permissions[$field->getId()] = $permissions;
    }

    public function setUgroupList(array $ugroup_list)
    {
        $this->ugroup_list = $ugroup_list;
    }

    /**
     * @return ByGroupUGroupListPresenter[]
     */
    public function getUgroupList(): array
    {
        return array_values($this->ugroup_list);
    }

    public function getMightNotHaveAccess(): bool
    {
        return $this->might_not_have_access;
    }

    public function setGroupsMightNotHaveAccess(bool $might_not_have_access)
    {
        $this->might_not_have_access = $might_not_have_access;
    }

    public function hasNoAccess(\Tracker_FormElement_Field $field) : bool
    {
        return !isset($this->field_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_READ']) && !isset($this->fields_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function hasSubmitPermission(\Tracker_FormElement_Field $field) : bool
    {
        return isset($this->fields_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_SUBMIT']);
    }

    public function hasReadOnlyPermission(\Tracker_FormElement_Field $field) : bool
    {
        return isset($this->fields_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_READ']) && !isset($this->fields_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function hasUpdatePermission(\Tracker_FormElement_Field $field) : bool
    {
        return isset($this->fields_permissions[$field->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function getFieldCount() : int
    {
        return count($this->fields);
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    public function getUgroupId(): int
    {
        return $this->ugroup_id;
    }

    public function getUgroupName(): string
    {
        return $this->ugroup_name;
    }

    public function addPermissionsForOtherGroups(\Tracker_FormElement_Field $field, int $id, string $name, array $permissions) : void
    {
        if (count($permissions) > 0) {
            $this->permissions_for_other_groups[$field->getId()][] = new ByGroupFieldsPermissionsForOtherGroups($id, $name, $permissions);
        }
    }

    /**
     * @return ByGroupFieldsPermissionsForOtherGroups[]
     */
    public function getPermissionsForOtherGroups(\Tracker_FormElement_Field $field)
    {
        if (! isset($this->permissions_for_other_groups[$field->getId()])) {
            return [];
        }
        return $this->permissions_for_other_groups[$field->getId()];
    }
}
