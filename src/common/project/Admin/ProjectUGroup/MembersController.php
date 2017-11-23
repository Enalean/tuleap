<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Codendi_Request;
use Feedback;
use ProjectUGroup;
use UserManager;

class MembersController
{
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(Codendi_Request $request, UserManager $user_manager)
    {
        $this->request      = $request;
        $this->user_manager = $user_manager;
    }

    public function editMembers(ProjectUGroup $ugroup)
    {
        $is_update_allowed = ! $ugroup->isBound();
        if (! $is_update_allowed) {
            return;
        }

        $project_id = $ugroup->getProjectId();
        $ugroup_id  = $ugroup->getId();

        $user_id = $this->request->get('remove_user');
        if ($user_id) {
            ugroup_remove_user_from_ugroup($project_id, $ugroup_id, $user_id);
        }
        $add_user_name = $this->request->get('add_user_name');
        if ($add_user_name) {
            $this->addUserByName($project_id, $ugroup_id, $add_user_name);
        }
    }

    private function addUserByName($project_id, $ugroup_id, $add_user_name)
    {
        $user = $this->user_manager->findUser($add_user_name);
        if ($user) {
            ugroup_add_user_to_ugroup($project_id, $ugroup_id, $user->getId());
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_account', 'user_not_exist')
            );
        }
    }
}
