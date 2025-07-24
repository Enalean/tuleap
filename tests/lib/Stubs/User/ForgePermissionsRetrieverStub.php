<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\User;

use PFUser;
use Tuleap\User\ForgePermissionsRetriever;
use User_ForgeUserGroupPermission;

final readonly class ForgePermissionsRetrieverStub implements ForgePermissionsRetriever
{
    private function __construct(private bool $allowed)
    {
    }

    public static function withPermission(): self
    {
        return new self(true);
    }

    public static function withoutPermission(): self
    {
        return new self(false);
    }

    #[\Override]
    public function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission): bool
    {
        return $this->allowed;
    }
}
