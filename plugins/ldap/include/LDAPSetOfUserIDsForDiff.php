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

namespace Tuleap\LDAP;

final class LDAPSetOfUserIDsForDiff
{
    /**
     * @var array
     */
    private $user_ids_to_add;
    /**
     * @var array
     */
    private $user_ids_to_remove;
    /**
     * @var array
     */
    private $user_ids_not_impacted;

    public function __construct(array $user_ids_to_add, array $user_ids_to_remove, array $user_ids_not_impacted)
    {
        $this->user_ids_to_add       = $user_ids_to_add;
        $this->user_ids_to_remove    = $user_ids_to_remove;
        $this->user_ids_not_impacted = $user_ids_not_impacted;
    }

    public function getUserIDsToAdd(): array
    {
        return $this->user_ids_to_add;
    }

    public function getUserIDsToRemove(): array
    {
        return $this->user_ids_to_remove;
    }

    public function getUserIDsNotImpacted(): array
    {
        return $this->user_ids_not_impacted;
    }
}
