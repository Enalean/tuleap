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

class ByFieldOneUGroupPresenter
{
    /**
     * @var int
     */
    public $ugroup_id;
    /**
     * @var string
     */
    public $ugroup_name;
    /**
     * @var int
     */
    public $field_id;
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
     * @var bool
     */
    public $might_not_have_access;

    public function __construct(ByFieldUGroup $ugroup, ByFieldGroupPermissions $fields_permission_for_group)
    {
        $this->ugroup_id         = $ugroup->getId();
        $this->ugroup_name       = $ugroup->getName();
        $this->might_not_have_access = $ugroup->getMightNotHaveAccess();
        $this->field_id          = $fields_permission_for_group->getFieldId();
        $this->has_submit_access = $fields_permission_for_group->hasSubmitPermission($ugroup);
        $this->not_submitable    = ! $fields_permission_for_group->getField()->isSubmitable();
        $this->is_updatable      = $fields_permission_for_group->getField()->isUpdateable();
        $this->has_no_access     = $fields_permission_for_group->hasNoAccess($ugroup);
        $this->has_read_access   = $fields_permission_for_group->hasReadOnlyPermission($ugroup);
        $this->has_update_access = $fields_permission_for_group->hasUpdatePermission($ugroup);
    }
}
