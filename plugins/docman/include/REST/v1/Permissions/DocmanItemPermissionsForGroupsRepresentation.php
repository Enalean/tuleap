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
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Permissions;

use Tuleap\Project\REST\UserGroupRepresentation;

final class DocmanItemPermissionsForGroupsRepresentation
{
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     * @psalm-var \Tuleap\Project\REST\UserGroupRepresentation[]
     */
    public $can_read = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     * @psalm-var \Tuleap\Project\REST\UserGroupRepresentation[]
     */
    public $can_write = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     * @psalm-var \Tuleap\Project\REST\UserGroupRepresentation[]
     */
    public $can_manage = [];

    /**
     * @param UserGroupRepresentation[] $can_read
     * @param UserGroupRepresentation[] $can_write
     * @param UserGroupRepresentation[] $can_manage
     */
    public static function build(array $can_read, array $can_write, array $can_manage): self
    {
        $representation             = new self();
        $representation->can_read   = $can_read;
        $representation->can_write  = $can_write;
        $representation->can_manage = $can_manage;
        return $representation;
    }
}
