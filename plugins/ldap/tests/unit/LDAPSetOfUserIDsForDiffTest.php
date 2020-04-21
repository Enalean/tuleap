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

use PHPUnit\Framework\TestCase;

final class LDAPSetOfUserIDsForDiffTest extends TestCase
{
    public function testValuesArePreserved(): void
    {
        $users_to_add       = [102];
        $users_to_remove    = [103];
        $users_not_impacted = [104];

        $set_of_user_ids = new LDAPSetOfUserIDsForDiff($users_to_add, $users_to_remove, $users_not_impacted);

        $this->assertEquals($users_to_add, $set_of_user_ids->getUserIDsToAdd());
        $this->assertEquals($users_to_remove, $set_of_user_ids->getUserIDsToRemove());
        $this->assertEquals($users_not_impacted, $set_of_user_ids->getUserIDsNotImpacted());
    }
}
