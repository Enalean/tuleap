<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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


namespace Tuleap\MediawikiStandalone\Permissions;

final class IBuildUserPermissionsStub implements IBuildUserPermissions
{
    private function __construct(private UserPermissions $permissions)
    {
    }

    public static function buildWithFullAccess(): self
    {
        return new self(UserPermissions::fullAccess());
    }

    public static function buildWithWriter(): self
    {
        return new self(UserPermissions::writer());
    }

    public static function buildWithReader(): self
    {
        return new self(UserPermissions::reader());
    }

    public static function buildWithNoAccess(): self
    {
        return new self(UserPermissions::noAccess());
    }

    #[\Override]
    public function getPermissions(\PFUser $user, \Project $project): UserPermissions
    {
        return $this->permissions;
    }
}
