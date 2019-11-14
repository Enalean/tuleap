<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup\Details;

use Event;
use EventManager;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
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
    /**
     * @var SynchronizedProjectMembershipDetector
     */
    private $detector;

    public function __construct(
        EventManager $event_manager,
        UserHelper $user_helper,
        SynchronizedProjectMembershipDetector $detector
    ) {
        $this->event_manager = $event_manager;
        $this->user_helper   = $user_helper;
        $this->detector      = $detector;
    }

    public function build(ProjectUGroup $ugroup)
    {
        $can_be_updated = ! $ugroup->isBound();
        $this->event_manager->processEvent(
            Event::UGROUP_UPDATE_USERS_ALLOWED,
            array('ugroup_id' => $ugroup->getId(), 'allowed' => &$can_be_updated)
        );

        $members                              = $this->getFormattedUgroupMembers($ugroup);
        $is_dynamic_group                     = ! $ugroup->isStatic();
        $is_synchronized_with_project_members = $this->detector->isSynchronizedWithProjectMembers($ugroup->getProject());

        return new
        MembersPresenter(
            $members,
            $can_be_updated,
            $is_dynamic_group,
            $is_synchronized_with_project_members
        );
    }

    private function getFormattedUgroupMembers(ProjectUGroup $ugroup)
    {
        $ugroup_members = array();

        $ugroup_members_updatable = new ProjectUGroupMemberUpdatable($ugroup);
        $this->event_manager->processEvent($ugroup_members_updatable);

        $members = $ugroup->getMembersIncludingSuspendedAndDeleted();

        foreach ($members as $member) {
            $ugroup_members[] = new MemberPresenter($this->user_helper, $member, $ugroup, $ugroup_members_updatable);
        }

        return $ugroup_members;
    }
}
