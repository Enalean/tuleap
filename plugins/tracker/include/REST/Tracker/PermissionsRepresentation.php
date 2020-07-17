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

namespace Tuleap\Tracker\REST\Tracker;

/**
 * @psalm-immutable
 */
class PermissionsRepresentation
{
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_access = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_access_submitted_by_user = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_access_assigned_to_group = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_access_submitted_by_group = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_admin = [];

    public function __construct(array $can_access, array $can_access_what_they_submitted, array $can_access_assigned_to_group, array $can_access_submitted_by_group, array $can_admin)
    {
        $this->can_access                    = $can_access;
        $this->can_access_submitted_by_user  = $can_access_what_they_submitted;
        $this->can_access_assigned_to_group  = $can_access_assigned_to_group;
        $this->can_access_submitted_by_group = $can_access_submitted_by_group;
        $this->can_admin                     = $can_admin;
    }
}
