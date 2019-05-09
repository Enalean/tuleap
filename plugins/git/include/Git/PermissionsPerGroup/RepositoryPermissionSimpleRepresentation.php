<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\PermissionsPerGroup;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;

class RepositoryPermissionSimpleRepresentation
{
    /**
     * @var bool
     */
    public $has_fined_grained_permissions;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $readers;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $writers;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $rewinders;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $url;

    public function __construct(
        $name,
        $url,
        array $readers,
        array $writers,
        array $rewinders
    ) {
        $this->has_fined_grained_permissions = false;
        $this->readers                       = $readers;
        $this->writers                       = $writers;
        $this->rewinders                     = $rewinders;
        $this->name                          = $name;
        $this->url                           = $url;
    }
}
