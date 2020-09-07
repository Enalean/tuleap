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

namespace Tuleap\ProjectOwnership\ProjectAdmin;

class ProjectOwnerPresenter
{
    public $project_owner_description;
    /** @var bool */
    public $has_project_owner;
    /** @var bool */
    public $has_avatar;
    public $avatar_url;
    public $username_display;
    public $user_name;

    /** @var \UserHelper */
    private $user_helper;

    public function __construct(\UserHelper $user_helper, \BaseLanguage $language)
    {
        $this->user_helper               = $user_helper;
        $this->project_owner_description = dgettext('tuleap-project_ownership', 'Project owner is accountable for project visibility, permissions & groups membership.');
    }

    public function build(?\PFUser $project_owner = null)
    {
        $this->has_project_owner = $project_owner !== null;
        if ($this->has_project_owner) {
            $this->has_avatar       = $project_owner->hasAvatar();
            $this->avatar_url       = $project_owner->getAvatarUrl();
            $this->user_name        = $project_owner->getUserName();
            $this->username_display = $this->user_helper->getDisplayNameFromUser($project_owner);
        }
    }
}
