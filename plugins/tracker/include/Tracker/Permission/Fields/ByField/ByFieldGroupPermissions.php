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

namespace Tuleap\Tracker\Permission\Fields\ByField;

class ByFieldGroupPermissions
{
    /**
     * @var ByFieldUGroup[]
     */
    private $ugroups = [];

    /**
     * @var array
     */
    private $ugroups_permissions = [];
    /**
     * @var ByFieldFieldListPresenter[]
     */
    private $field_list;
    /**
     * @var bool
     */
    private $might_not_have_access;
    /**
     * @var \Tracker_FormElement_Field
     */
    private $field;

    public function __construct(\Tracker_FormElement_Field $field)
    {
        $this->field = $field;
    }

    public function addUGroup(int $id, string $name, array $permissions, array $tracker_permissions): void
    {
        $this->ugroups[$id]             = new ByFieldUGroup($id, $name, count($tracker_permissions) === 0);
        $this->ugroups_permissions[$id] = $permissions;
        if (count($tracker_permissions) === 0) {
            $this->might_not_have_access = true;
        }
    }

    public function setFieldList(array $field_list): void
    {
        $this->field_list = $field_list;
    }

    /**
     * @return ByFieldFieldListPresenter[]
     */
    public function getFieldList(): array
    {
        return array_values($this->field_list);
    }

    public function hasNoAccess(ByFieldUGroup $ugroup): bool
    {
        return !isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_READ']) && !isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function hasSubmitPermission(ByFieldUGroup $ugroup): bool
    {
        return isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_SUBMIT']);
    }

    public function hasReadOnlyPermission(ByFieldUGroup $ugroup): bool
    {
        return isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_READ']) && !isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function hasUpdatePermission(ByFieldUGroup $ugroup): bool
    {
        return isset($this->ugroups_permissions[$ugroup->getId()]['PLUGIN_TRACKER_FIELD_UPDATE']);
    }

    public function getUGroupCount(): int
    {
        return count($this->ugroups);
    }

    /**
     * @return ByFieldUGroup[]
     */
    public function getUgroupsOrderedWithPrecedence(): array
    {
        $ugroups = $this->ugroups;
        ksort($ugroups);
        return $ugroups;
    }

    public function getFieldId(): int
    {
        return (int) $this->field->getId();
    }

    public function getFieldName(): string
    {
        return $this->field->getLabel();
    }

    public function getField(): \Tracker_FormElement_Field
    {
        return $this->field;
    }

    public function getMightNotHaveAccess(): bool
    {
        return $this->might_not_have_access;
    }
}
