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

namespace Tuleap\Project\Admin\ProjectUGroup;

class ImpossibleToMarkNotUGroupMemberAsNotUpdatableException extends \RuntimeException
{
    public function __construct(\ProjectUGroup $group, \PFUser $user)
    {
        $ugroup_id = $group->getId();
        $user_id   = $user->getId();
        parent::__construct(
            "The user #$user_id is not part of #$ugroup_id so he can not be marked as not updatable"
        );
    }
}
