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

        $members          = $this->getFormattedProjectMembers($ugroup);
        $is_dynamic_group = (int) $ugroup->getId() === ProjectUGroup::PROJECT_ADMIN;

        return new MembersPresenter($members, $can_be_updated, $is_dynamic_group);
    }

    private function getFormattedProjectMembers(ProjectUGroup $ugroup)
    {
        $ugroup_members = array();

        $members        = $ugroup->getMembers();
        $can_be_deleted = (int) $ugroup->getId() !== ProjectUGroup::PROJECT_ADMIN || count($members) > 1;

        foreach ($members as $key => $member) {
            $ugroup_members[$key]['profile_page_url'] = "/users/" . urlencode($member->getUserName()) . "/";

            $ugroup_members[$key]['username_display'] = $this->user_helper->getDisplayName(
                $member->getUserName(),
                $member->getRealName()
            );

            $ugroup_members[$key]['has_avatar']     = $member->hasAvatar();
            $ugroup_members[$key]['user_name']      = $member->getUserName();
            $ugroup_members[$key]['user_id']        = $member->getId();
            $ugroup_members[$key]['can_be_deleted'] = $can_be_deleted;
        }

        return $ugroup_members;
    }
}
