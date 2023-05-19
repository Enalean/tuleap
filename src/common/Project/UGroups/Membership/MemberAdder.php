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

namespace Tuleap\Project\UGroups\Membership;

use EventManager;
use ForgeConfig;
use PFUser;
use Project;
use ProjectHistoryDao;
use ProjectUGroup;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\UserPermissionsDao;
use UGroup_Invalid_Exception;

class MemberAdder
{
    /** @var MembershipUpdateVerifier */
    private $membership_update_verifier;
    /** @var StaticMemberAdder */
    private $static_member_adder;
    /** @var DynamicUGroupMembersUpdater */
    private $dynamic_member_updater;
    /** @var ProjectMemberAdder */
    private $project_member_adder;
    /** @var SynchronizedProjectMembershipDetector */
    private $synchronized_project_membership_detector;

    public function __construct(
        MembershipUpdateVerifier $membership_update_verifier,
        StaticMemberAdder $static_member_adder,
        DynamicUGroupMembersUpdater $dynamic_member_updater,
        ProjectMemberAdder $project_member_adder,
        SynchronizedProjectMembershipDetector $synchronized_project_membership_detector,
    ) {
        $this->membership_update_verifier               = $membership_update_verifier;
        $this->static_member_adder                      = $static_member_adder;
        $this->dynamic_member_updater                   = $dynamic_member_updater;
        $this->project_member_adder                     = $project_member_adder;
        $this->synchronized_project_membership_detector = $synchronized_project_membership_detector;
    }

    public static function build(ProjectMemberAdder $project_member_adder): self
    {
        return new MemberAdder(
            new MembershipUpdateVerifier(),
            new StaticMemberAdder(),
            new DynamicUGroupMembersUpdater(
                new UserPermissionsDao(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                ),
                $project_member_adder,
                EventManager::instance(),
                new ProjectHistoryDao(),
            ),
            $project_member_adder,
            new SynchronizedProjectMembershipDetector(
                new SynchronizedProjectMembershipDao()
            )
        );
    }

    /**
     * @throws InvalidProjectException
     * @throws UGroup_Invalid_Exception
     * @throws UserIsAnonymousException
     */
    public function addMember(PFUser $user, ProjectUGroup $ugroup, PFUser $project_admin): void
    {
        $this->membership_update_verifier->assertUGroupAndUserValidity($user, $ugroup);
        $project = $ugroup->getProject();
        if ($project === null) {
            throw new UGroup_Invalid_Exception();
        }

        if (
            $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED
            && ForgeConfig::areRestrictedUsersAllowed()
            && $user->isRestricted()
        ) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }

        if (! $ugroup->isStatic()) {
            $this->dynamic_member_updater->addUser($project, $ugroup, $user, $project_admin);
            return;
        }

        $this->addToStaticUGroup($user, $ugroup, $project, $project_admin);
    }

    /**
     * @throws UGroup_Invalid_Exception
     */
    private function addToStaticUGroup(PFUser $user, ProjectUGroup $ugroup, Project $project, PFUser $project_admin): void
    {
        $project_id = $project->getID();
        $ugroup_id  = $ugroup->getId();
        if (! $ugroup->exists($project_id, $ugroup_id)) {
            throw new UGroup_Invalid_Exception();
        }
        $this->static_member_adder->addUserToStaticGroup($project_id, $ugroup_id, $user->getId());

        if (
            $this->synchronized_project_membership_detector->isSynchronizedWithProjectMembers($project)
            && ! $user->isMember($project_id)
        ) {
            $this->project_member_adder->addProjectMemberWithFeedback($user, $project, $project_admin);
        }
    }
}
