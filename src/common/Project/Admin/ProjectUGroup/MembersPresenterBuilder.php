<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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

use Event;
use EventManager;
use ProjectUGroup;
use UserHelper;

class MembersPresenterBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(EventManager $event_manager, UserHelper $user_helper)
    {
        $this->event_manager = $event_manager;
        $this->user_helper   = $user_helper;
    }

    public function build(ProjectUGroup $ugroup)
    {
        $can_be_updated = ! $ugroup->isBound();
        $this->event_manager->processEvent(
            Event::UGROUP_UPDATE_USERS_ALLOWED,
            array('ugroup_id' => $ugroup->getId(), 'allowed' => &$can_be_updated)
        );

        $members          = $this->getFormattedUgroupMembers($ugroup);
        $is_dynamic_group = ! $ugroup->isStatic();

        return new MembersPresenter($members, $can_be_updated, $is_dynamic_group);
    }

    private function getFormattedUgroupMembers(ProjectUGroup $ugroup)
    {
        $ugroup_members = array();

        $ugroup_members_updatable = new ProjectUGroupMemberUpdatable($ugroup);
        $this->event_manager->processEvent($ugroup_members_updatable);

        $members                   = $ugroup->getMembersIncludingSuspended();

        foreach ($members as $key => $member) {
            $ugroup_members[$key]['profile_page_url'] = "/users/" . urlencode($member->getUserName()) . "/";

            $ugroup_members[$key]['username_display'] = $this->user_helper->getDisplayName(
                $member->getUserName(),
                $member->getRealName()
            );

            $is_news_admin = false;
            if ((int) $ugroup->getId() === ProjectUGroup::NEWS_WRITER
                && $member->isMember($ugroup->getProjectId(), "N2")) {
                $is_news_admin = true;
            }

            $ugroup_members[$key]['has_avatar']                = $member->hasAvatar();
            $ugroup_members[$key]['user_name']                 = $member->getUserName();
            $ugroup_members[$key]['user_id']                   = $member->getId();
            $updatable_error_messages                          = $ugroup_members_updatable->getUserUpdatableErrorMessages($member);
            $ugroup_members[$key]['is_member_updatable']       = count($updatable_error_messages) === 0;
            $ugroup_members[$key]['member_updatable_messages'] = $updatable_error_messages;
            $ugroup_members[$key]['is_news_admin']             = $is_news_admin;
        }

        return $ugroup_members;
    }
}
