<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\User;

class DashboardPresenter
{
    public $user_id;
    public $name;
    public $id;

    public $is_active;

    public function __construct(Dashboard $dashboard, $is_active)
    {
        $this->id      = $dashboard->getId();
        $this->user_id = $dashboard->getUserId();
        $this->name    = $dashboard->getName();

        $this->is_active = $is_active;
    }
}
