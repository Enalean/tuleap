<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use DataAccessObject;

class MembershipDelegationDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function doesUserHasMembershipDelegation($user_id, $project_id)
    {
        $user_id    = $this->da->escapeInt($user_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT NULL
                FROM project_membership_delegation AS delegation
                    INNER JOIN ugroup ON (
                        delegation.ugroup_id = ugroup.ugroup_id
                        AND ugroup.group_id = $project_id
                    )
                    INNER JOIN ugroup_user ON (
                        ugroup_user.ugroup_id = ugroup.ugroup_id
                        AND ugroup_user.user_id = $user_id
                    )";

        return count($this->retrieve($sql)) > 0;
    }
}
