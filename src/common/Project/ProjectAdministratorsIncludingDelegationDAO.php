<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project;

use Tuleap\DB\DataAccessObject;

class ProjectAdministratorsIncludingDelegationDAO extends DataAccessObject
{
    /**
     * @return string[]
     */
    public function searchAdministratorEmailsIncludingDelegatedAccess(int $project_id): array
    {
        $sql = 'SELECT user.email AS email
            FROM user,user_group
		    WHERE user_group.user_id=user.user_id AND user_group.group_id = ? AND user_group.admin_flags = "A"
		    UNION
		    SELECT user.email AS email
		    FROM user
            JOIN ugroup_user ON (user.user_id = ugroup_user.user_id)
            JOIN ugroup ON (ugroup_user.ugroup_id = ugroup.ugroup_id AND ugroup.group_id = ?)
            JOIN project_membership_delegation AS delegation ON (ugroup_user.ugroup_id = delegation.ugroup_id)';

        return $this->getDB()->col($sql, 0, $project_id, $project_id);
    }
}
