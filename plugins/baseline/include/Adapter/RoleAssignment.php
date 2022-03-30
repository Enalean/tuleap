<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

use Project;

/**
 * Assign a pair project / user group id to a role, which gives permissions.
 */
class RoleAssignment
{
    /** @var Project */
    private $project;

    /** @var int */
    private $user_group_id;

    /** @var string */
    private $role;

    public function __construct(Project $project, int $user_group_id, string $role)
    {
        $this->project       = $project;
        $this->user_group_id = $user_group_id;
        $this->role          = $role;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUserGroupId(): int
    {
        return $this->user_group_id;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
