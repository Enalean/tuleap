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

class ByFieldOneUGroupWithFieldListPresenter extends ByFieldOneUGroupPresenter
{
    public $is_first = true;
    /**
     * @var ByFieldFieldListPresenter[]
     */
    public $field_list;
    /**
     * @var int
     */
    public $nb_permissions;

    public function __construct(ByFieldUGroup $ugroup, ByFieldGroupPermissions $ugroup_permissions_for_field)
    {
        parent::__construct($ugroup, $ugroup_permissions_for_field);
        $this->nb_permissions = $ugroup_permissions_for_field->getUGroupCount() + 1;
        $this->field_list = $ugroup_permissions_for_field->getFieldList();
    }
}
