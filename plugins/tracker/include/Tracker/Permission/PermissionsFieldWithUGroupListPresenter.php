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

namespace Tuleap\Tracker\Permission;

class PermissionsFieldWithUGroupListPresenter extends PermissionsFieldPresenter
{
    public $is_first = true;
    /**
     * @var PermissionsUGroupListPresenter[]
     */
    public $ugroup_list;
    /**
     * @var int
     */
    public $nb_permissions;

    public function __construct(
        int $field_id,
        string $field_name,
        int $ugroup_id,
        bool $is_checked,
        bool $can_submit,
        bool $can_update,
        bool $has_no_access,
        bool $has_read_access,
        bool $has_update_access,
        int $nb_permissions,
        array $ugroup_list
    ) {
        parent::__construct($field_id, $field_name, $ugroup_id, $is_checked, $can_submit, $can_update, $has_no_access, $has_read_access, $has_update_access);
        $this->nb_permissions = $nb_permissions;
        $this->ugroup_list = $ugroup_list;
    }
}
