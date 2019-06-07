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

class ByGroupOneFieldWithUGroupListPresenter extends ByGroupOneFieldPresenter
{
    public $is_first = true;
    /**
     * @var ByGroupUGroupListPresenter[]
     */
    public $ugroup_list;
    /**
     * @var int
     */
    public $nb_permissions;

    public function __construct(\Tracker_FormElement_Field $field, ByGroupFieldsPermissions $fields_permission_for_group)
    {
        parent::__construct($field, $fields_permission_for_group);
        $this->nb_permissions = $fields_permission_for_group->getFieldCount() + 1;
        $this->ugroup_list = $fields_permission_for_group->getUgroupList();
    }
}
