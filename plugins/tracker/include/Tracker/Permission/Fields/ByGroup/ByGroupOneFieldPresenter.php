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

class ByGroupOneFieldPresenter
{
    /**
     * @var int
     */
    public $field_id;
    /**
     * @var string
     */
    public $field_label;
    /**
     * @var int
     */
    public $ugroup_id;
    /**
     * @var bool
     */
    public $has_submit_access;
    /**
     * @var bool
     */
    public $not_submitable;
    /**
     * @var bool
     */
    public $is_first = false;
    /**
     * @var bool
     */
    public $is_updatable;
    /**
     * @var bool
     */
    public $has_no_access;
    /**
     * @var bool
     */
    public $has_read_access;
    /**
     * @var bool
     */
    public $has_update_access;
    /**
     * @var array
     */
    public $other_groups = [];
    /**
     * @var bool
     */
    public $has_other_groups = false;

    public function __construct(\Tracker_FormElement_Field $field, ByGroupFieldsPermissions $fields_permission_for_group)
    {
        $this->field_id          = $field->getId();
        $this->field_label       = $field->getLabel();
        $this->ugroup_id         = $fields_permission_for_group->getUgroupId();
        $this->has_submit_access = $fields_permission_for_group->hasSubmitPermission($field);
        $this->not_submitable    = ! $field->isSubmitable();
        $this->is_updatable      = $field->isUpdateable();
        $this->has_no_access     = $fields_permission_for_group->hasNoAccess($field);
        $this->has_read_access   = $fields_permission_for_group->hasReadOnlyPermission($field);
        $this->has_update_access = $fields_permission_for_group->hasUpdatePermission($field);
        foreach ($fields_permission_for_group->getPermissionsForOtherGroups($field) as $other_groups) {
            $this->has_other_groups = true;
            $this->other_groups[] = $other_groups;
        }
    }
}
