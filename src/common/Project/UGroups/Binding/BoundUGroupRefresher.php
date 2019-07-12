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
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Binding;

use Exception;
use LogicException;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\InvalidProjectException;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\Membership\UserIsAnonymousException;

class BoundUGroupRefresher
{
    /** @var \UGroupManager */
    private $ugroup_manager;
    /** @var \UGroupUserDao */
    private $ugroup_user_dao;
    /** @var MemberAdder */
    private $member_adder;

    public function __construct(
        \UGroupManager $ugroup_manager,
        \UGroupUserDao $ugroup_user_dao,
        MemberAdder $member_adder
    ) {
        $this->ugroup_manager  = $ugroup_manager;
        $this->ugroup_user_dao = $ugroup_user_dao;
        $this->member_adder    = $member_adder;
    }

    /**
     * @throws \Exception
     */
    public function refresh(ProjectUGroup $source, ProjectUGroup $destination): void
    {
        $destination_id = $destination->getId();
        if (! $this->ugroup_manager->isUpdateUsersAllowed($destination_id)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('project_ugroup_binding', 'update_user_not_allowed', [$destination_id])
            );
            throw new Exception(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error')
            );
        }
        try {
            $this->clearMembers($destination);
            $this->duplicateMembers($source, $destination);
        } catch (LogicException $e) {
            //re-throw exception
            throw new Exception($e->getMessage());
        }
    }

    private function clearMembers(ProjectUGroup $ugroup): void
    {
        $ugroup_id = $ugroup->getId();
        if ($this->ugroup_user_dao->resetUgroupUserList($ugroup_id) === false) {
            throw new LogicException(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'reset_error', [$ugroup_id])
            );
        }
    }

    /**
     * @throws InvalidProjectException
     * @throws UserIsAnonymousException
     * @throws \UGroup_Invalid_Exception
     */
    private function duplicateMembers(ProjectUGroup $source, ProjectUGroup $destination): void
    {
        $members = $source->getMembers();
        foreach ($members as $user) {
            try {
                $this->member_adder->addMember($user, $destination);
            } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted $e) {
                $GLOBALS['Response']->addFeedback(
                    \Feedback::ERROR,
                    sprintf(
                        _('The user #%d could not be duplicated into user group %s because its project does not allow restricted users.'),
                        $e->getRestrictedUser()->getId(),
                        $destination->getTranslatedName()
                    )
                );
            }
        }
    }
}
