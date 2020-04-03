<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup\Details;

use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\User\StatusPresenter;

class MemberPresenter
{
    public $profile_page_url;
    public $username_display;
    public $has_avatar;
    public $user_name;
    public $user_id;
    public $is_member_updatable;
    public $is_news_admin;
    public $member_updatable_messages;
    public $user_is_project_admin;
    /**
     * @var StatusPresenter
     */
    public $status_presenter;
    /**
     * @var string
     */
    public $avatar_url;

    public function __construct(\UserHelper $user_helper, \PFUser $member, ProjectUGroup $ugroup, ProjectUGroupMemberUpdatable $ugroup_members_updatable)
    {
        $this->profile_page_url = "/users/" . urlencode($member->getUserName()) . "/";

        $this->username_display = $user_helper->getDisplayName(
            $member->getUserName(),
            $member->getRealName()
        );

        $is_news_admin = false;
        if (
            (int) $ugroup->getId() === ProjectUGroup::NEWS_WRITER
            && $member->isMember($ugroup->getProjectId(), "N2")
        ) {
            $is_news_admin = true;
        }

        $this->has_avatar                = $member->hasAvatar();
        $this->avatar_url                = $member->getAvatarUrl();
        $this->user_name                 = $member->getUserName();
        $this->user_id                   = $member->getId();
        $updatable_error_messages        = $ugroup_members_updatable->getUserUpdatableErrorMessages($member);
        $this->is_member_updatable       = count($updatable_error_messages) === 0;
        $this->member_updatable_messages = $updatable_error_messages;
        $this->is_news_admin             = $is_news_admin;
        $this->user_is_project_admin     = (int) $member->isAdmin($ugroup->getProjectId());
        $this->status_presenter          = new StatusPresenter($member->getStatus());
    }
}
