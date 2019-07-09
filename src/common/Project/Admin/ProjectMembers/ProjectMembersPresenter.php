<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use CSRFSynchronizerToken;
use Project;

class ProjectMembersPresenter
{

    /**
     * @var array
     */
    public $project_members_list;
    public $csrf_token;
    public $project_id;

    /**
     * @var ProjectMembersAdditionalModalCollectionPresenter
     */
    public $additional_modals;
    public $user_locale;
    public $can_see_ugroups;
    /**
     * @var bool
     */
    public $is_synchronized_with_ugroups;

    public function __construct(
        array $project_members_list,
        CSRFSynchronizerToken $csrf_token,
        Project $project,
        ProjectMembersAdditionalModalCollectionPresenter $additional_modals,
        string $user_locale,
        bool $can_see_ugroups,
        bool $is_synchronized_with_ugroups
    ) {
        $this->project_members_list         = $project_members_list;
        $this->csrf_token                   = $csrf_token;
        $this->project_id                   = $project->getID();
        $this->additional_modals            = $additional_modals;
        $this->user_locale                  = $user_locale;
        $this->can_see_ugroups              = $can_see_ugroups;
        $this->is_synchronized_with_ugroups = $is_synchronized_with_ugroups;
    }
}
