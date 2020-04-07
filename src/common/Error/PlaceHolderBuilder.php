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

namespace Tuleap\Error;

use Project;

class PlaceHolderBuilder
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(\ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    public function buildPlaceHolder(Project $project)
    {
        $result = $this->project_manager->getMessageToRequesterForAccessProject($project->getID());

        $default_message = _("Please write something meaningful for the admin.");
        if (! $result) {
            return $default_message;
        }

        $row = $result->getRow();

        if (
            $row['msg_to_requester'] === "member_request_delegation_msg_to_requester"
            || $row['msg_to_requester'] === null
        ) {
            return $default_message;
        }

        return $row['msg_to_requester'];
    }
}
