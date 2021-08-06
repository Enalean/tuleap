<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserPermissions;

/**
 * I am a Proxy around PFUser permissions
 *
 * @psalm-immutable
 */
final class UserPermissionsProxy implements UserPermissions
{
    private bool $is_platform_admin;
    private bool $is_project_admin;

    private function __construct(bool $is_platform_admin, bool $is_project_admin)
    {
        $this->is_platform_admin = $is_platform_admin;
        $this->is_project_admin  = $is_project_admin;
    }

    public static function buildFromPFUser(\PFUser $user, ProgramIdentifier $program_identifier): self
    {
        return new self($user->isSuperUser(), $user->isAdmin($program_identifier->getId()));
    }


    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin;
    }

    public function isProjectAdmin(): bool
    {
        return $this->is_project_admin;
    }
}
