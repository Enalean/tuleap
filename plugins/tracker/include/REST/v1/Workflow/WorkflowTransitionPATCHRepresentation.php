<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tuleap\Project\REST\UserGroupRepresentation;

/**
 * @psalm-immutable
 */
class WorkflowTransitionPATCHRepresentation
{
    /**
     * @var string[] Authorized user group id {@type string}
     */
    public $authorized_user_group_ids;

    /**
     * @var int[] Ids of not empty fields {@type int}
     */
    public $not_empty_field_ids;

    /**
     * @var bool {@type bool}
     */
    public $is_comment_required;

    /**
     * @return array Ids of authorized user groups (without group id added in REST representation)
     */
    public function getAuthorizedUserGroupIds()
    {
        return array_map(function ($group_id) {
            return UserGroupRepresentation::getProjectAndUserGroupFromRESTId($group_id)['user_group_id'];
        }, $this->authorized_user_group_ids);
    }
}
