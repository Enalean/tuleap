<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use HTTPRequest;
use Valid_WhiteList;

class ProjectMembersRouter
{
    /**
     * @var ProjectMembersController
     */
    private $members_controller;

    /**
     * @var Valid_WhiteList
     */
    private $valid_Whitelist;

    public function __construct(
        ProjectMembersController $members_controller,
        Valid_WhiteList $valid_Whitelist
    ) {
        $this->members_controller = $members_controller;
        $this->valid_Whitelist    = $valid_Whitelist;
    }

    public function route(HTTPRequest $request)
    {
        if ($request->isPost() && $request->valid($this->valid_Whitelist)) {
            switch ($request->get('action')) {
                case 'add-user':
                    $this->members_controller->addUserToProject($request);
                    break;

                case 'remove-user':
                    $this->members_controller->removeUserFromProject($request);
                    break;
            }
        }

        $this->members_controller->display($request);
    }
}
