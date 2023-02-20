<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\InviteBuddy\Invitation;

final class PendingUsersDao extends DataAccessObject
{
    public function searchPendingUsers(): array
    {
        return $this->searchUsersWithStatus(\PFUser::STATUS_PENDING);
    }

    public function searchValidatedUsers(): array
    {
        return $this->searchUsersWithStatus(\PFUser::STATUS_VALIDATED, \PFUser::STATUS_VALIDATED_RESTRICTED);
    }

    public function searchPendingAndValidatedUsers(): array
    {
        return $this->searchUsersWithStatus(\PFUser::STATUS_PENDING, \PFUser::STATUS_VALIDATED, \PFUser::STATUS_VALIDATED_RESTRICTED);
    }

    /**
     * @param \PFUser::STATUS_* ...$status
     *
     * @psalm-return list<array{ user_id: int, user_name: string, realname: string, email: string, add_date: int, register_purpose: string, expiry_date: int, status: string, from_user_id: ?int, to_project_id: ?int }>
     */
    private function searchUsersWithStatus(string ...$status): array
    {
        $status_condition = EasyStatement::open()->in('user.status IN (?*)', $status);

        $sql = "SELECT user.*, invitations.from_user_id, invitations.to_project_id
            FROM user
            LEFT JOIN invitations ON (user.user_id = invitations.created_user_id AND invitations.status = ?)
            WHERE $status_condition";

        return $this->getDB()->run($sql, Invitation::STATUS_USED, ...$status_condition->values());
    }
}
