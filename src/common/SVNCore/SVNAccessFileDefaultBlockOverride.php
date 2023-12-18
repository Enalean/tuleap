<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore;

use Tuleap\Event\Dispatchable;

final class SVNAccessFileDefaultBlockOverride implements Dispatchable
{
    public readonly array $user_groups;

    private ?array $svn_user_groups;
    private bool $world_access = true;

    public function __construct(public readonly \Project $project, \ProjectUGroup ...$user_groups)
    {
        $this->user_groups = $user_groups;
    }

    public function addSVNGroup(SVNUserGroup $group): void
    {
        $this->svn_user_groups[] = $group;
    }

    public function disableWorldAccess(): void
    {
        $this->world_access = false;
    }

    public function isWorldAccessForbidden(): bool
    {
        return $this->world_access === false;
    }

    /**
     * @return SVNUserGroup[]
     */
    public function getSVNUserGroups(): array
    {
        if (! isset($this->svn_user_groups)) {
            $this->svn_user_groups = array_map(
                static fn (\ProjectUGroup $group): SVNUserGroup => SVNUserGroup::fromUserGroup($group),
                $this->user_groups
            );
        }
        return $this->svn_user_groups;
    }
}
