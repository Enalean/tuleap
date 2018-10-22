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

namespace Tuleap\Project\Admin\ProjectMembers;

use UserManager;

class ProjectMemberAutocompleteValueExtractor
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @param \HTTPRequest $request
     *
     * @return null|\PFUser
     */
    public function getUserFromRequest(\HTTPRequest $request)
    {
        $user_info = $request->get('new_project_member');
        if (! $user_info) {
            return null;
        }

        $user = $this->user_manager->getUserById($user_info);
        if ($user) {
            return $user;
        }

        return null;
    }
}
