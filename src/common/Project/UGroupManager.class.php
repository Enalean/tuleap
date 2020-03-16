<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\User\UserGroup\NameTranslator;

require_once __DIR__ . '/../../www/include/account.php';

class UGroupManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private const FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS = 100;

    /**
     * @var UGroupDao
     */
    private $dao;

    /**
     * @var UGroupUserDao
     */
    private $ugroup_user_dao;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var ProjectMemberAdder|null
     */
    private $project_member_adder;

    public function __construct(
        ?UGroupDao $dao = null,
        ?EventManager $event_manager = null,
        ?UGroupUserDao $ugroup_user_dao = null,
        ?DynamicUGroupMembersUpdater $dynamic_ugroup_members_updater = null,
        ?ProjectMemberAdder $project_member_adder = null
    ) {
        $this->dao                            = $dao;
        $this->event_manager                  = $event_manager;
        $this->ugroup_user_dao                = $ugroup_user_dao;
        $this->dynamic_ugroup_members_updater = $dynamic_ugroup_members_updater;
        $this->project_member_adder           = $project_member_adder;
    }

    /**
     * @return UGroupUserDao
     */
    private function getUGroupUserDao()
    {
        if (empty($this->ugroup_user_dao)) {
            $this->ugroup_user_dao = new UGroupUserDao();
        }
        return $this->ugroup_user_dao;
    }

    private function getProjectMemberAdder() : ProjectMemberAdder
    {
        if (! $this->project_member_adder) {
            $this->project_member_adder = ProjectMemberAdderWithStatusCheckAndNotifications::build();
        }
        return $this->project_member_adder;
    }

    /**
     *
     * @param type $ugroup_id
     *
     * @return ProjectUGroup
     */
    public function getUGroupWithMembers(Project $project, $ugroup_id)
    {
        $ugroup = $this->getUGroup($project, $ugroup_id);

        if (! $ugroup) {
            return null;
        }

        $ugroup->getMembers();

        return $ugroup;
    }

    /**
     * @return ProjectUGroup|null
     */
    public function getUGroup(Project $project, $ugroup_id)
    {
        $project_id = $project->getID();
        if ($ugroup_id <= 100) {
            $project_id = self::FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS;
        }

        $row = $this->getDao()->searchByGroupIdAndUGroupId($project_id, $ugroup_id)->getRow();

        if ($row) {
            return $this->instanciateGroupForProject($project, $row);
        }

        return null;
    }

    /**
     * @return ProjectUGroup
     */
    public function getProjectAdminsUGroup(Project $project)
    {
        $row = $this->getDao()->searchByGroupIdAndUGroupId(self::FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS, ProjectUGroup::PROJECT_ADMIN)->getRow();
        return $this->instanciateGroupForProject($project, $row);
    }

    /**
     * @return ProjectUGroup
     */
    public function getProjectMembersUGroup(Project $project)
    {
        $row = $this->getDao()->searchByGroupIdAndUGroupId(self::FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS, ProjectUGroup::PROJECT_MEMBERS)->getRow();
        return $this->instanciateGroupForProject($project, $row);
    }

    /**
     * @return DynamicUGroupMembersUpdater
     */
    private function getDynamicUGroupMembersUpdater()
    {
        if ($this->dynamic_ugroup_members_updater === null) {
            $this->dynamic_ugroup_members_updater = new DynamicUGroupMembersUpdater(
                new UserPermissionsDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                ProjectMemberAdderWithStatusCheckAndNotifications::build(),
                $this->getEventManager()
            );
        }
        return $this->dynamic_ugroup_members_updater;
    }

    /**
     * @return ProjectUGroup
     */
    public function instanciateGroupForProject(Project $project, array $row)
    {
        // force group_id as it is set to 100 for dynamic groups
        $row['group_id'] = $project->getID();
        return new ProjectUGroup($row);
    }

    /**
     * @see self::getUGroupsWithSystemUserGroups() returns same groups, except system user groups and Nobody.
     *
     * @param array $excluded_ugroups_id
     * @return ProjectUGroup[]
     */
    public function getUGroups(Project $project, array $excluded_ugroups_id = array())
    {
        $ugroups = array();
        foreach ($this->getDao()->searchDynamicAndStaticByGroupId($project->getId()) as $row) {
            if (in_array($row['ugroup_id'], $excluded_ugroups_id)) {
                continue;
            }
            $ugroups[] = $this->instanciateGroupForProject($project, $row);
        }
        return $ugroups;
    }

    /**
     * Find all available user groups of a given project, except Nobody group.
     * System user groups are excluded if restricted by platform configuration.
     * @see self::getUgroups() returns all groups.
     *
     * @return ProjectUGroup[]
     */
    public function getAvailableUGroups(Project $project)
    {
        $user_groups = $this->getUGroups($project);

        return array_filter($user_groups, function (ProjectUGroup $ugroup) use ($project) {
            if ($ugroup->getId() == ProjectUgroup::ANONYMOUS) {
                return ForgeConfig::areAnonymousAllowed() && $project->isPublic();
            }
            if ($ugroup->getId() == ProjectUgroup::AUTHENTICATED) {
                return ForgeConfig::areRestrictedUsersAllowed() && $project->allowsRestricted();
            }
            if ($ugroup->getId() == ProjectUgroup::REGISTERED) {
                return $project->isPublic();
            }
            if ($ugroup->getId() == ProjectUgroup::NONE) {
                return false;
            }

            return true;
        });
    }

    /**
     * @return array of projectUGroup indexed by their id
     */
    public function getUgroupsById(Project $project)
    {
        $ugroups = array();
        foreach ($this->getDao()->searchDynamicAndStaticByGroupId($project->getId()) as $row) {
            $ug = $this->instanciateGroupForProject($project, $row);
            $ugroups[$ug->getId()] = $ug;
        }
        return $ugroups;
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getStaticUGroups(Project $project)
    {
        $ugroups = array();
        foreach ($this->getDao()->searchStaticByGroupId($project->getId()) as $row) {
            $ugroups[] = $this->instanciateGroupForProject($project, $row);
        }
        return $ugroups;
    }

    /**
     * @return ProjectUGroup
     */
    public function getUGroupByName(Project $project, $name)
    {
        $row = $this->getDao()->searchByGroupIdAndName($project->getID(), $name)->getRow();
        if (! $row && preg_match('/^ugroup_.*_key$/', $name)) {
            $row = $this->getDao()->searchByGroupIdAndName(self::FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS, $name)->getRow();
        }
        if (! $row && in_array($this->getUnormalisedName($name), NameTranslator::$names)) {
            $row = $this->getDao()->searchByGroupIdAndName(self::FAKE_PROJECT_ID_FOR_DYNAMIC_GROUPS, $this->getUnormalisedName($name))->getRow();
        }
        if (! $row && $ugroup = $this->getDynamicUGoupByName($project, $name)) {
            return $ugroup;
        }
        if ($row) {
            return new ProjectUGroup($row);
        }
        return null;
    }

    public function getDynamicUGoupIdByName($name)
    {
        return array_search($name, ProjectUGroup::$normalized_names);
    }

    public function getDynamicUGoupByName(Project $project, $name)
    {
        $ugroup_id = $this->getDynamicUGoupIdByName($name);
        if (empty($ugroup_id)) {
            return null;
        }
        return new ProjectUGroup(array(
            'ugroup_id' => $ugroup_id,
            'name'      => $name,
            'group_id'  => $project->getID()
        ));
    }

    private function getUnormalisedName($name)
    {
        return 'ugroup_' . $name . '_name_key';
    }

    public function getLabel($group_id, $ugroup_id)
    {
        $row = $this->getDao()->searchNameByGroupIdAndUGroupId($group_id, $ugroup_id)->getRow();
        if (! $row) {
            return '';
        }

        return $row['name'];
    }

    /**
     * Return all UGroups the user belongs to
     *
     * @param PFUser $user The user
     *
     * @return ProjectUGroup[]
     */
    public function getByUserId($user)
    {
        $ugroups = array();
        $dar     = $this->getDao()->searchByUserId($user->getId());

        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                $ugroups[] = new ProjectUGroup($row);
            }
        }

        return $ugroups;
    }

    /**
     * Returns a ProjectUGroup from its Id
     *
     * @param int $ugroupId The UserGroupId
     *
     * @return ProjectUGroup
     */
    public function getById($ugroupId)
    {
        $dar = $this->getDao()->searchByUGroupId($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new ProjectUGroup($dar->getRow());
        } else {
            return new ProjectUGroup();
        }
    }

    /**
     * Wrapper for UGroupDao
     *
     * @return UGroupDao
     */
    public function getDao()
    {
        if (!$this->dao) {
            $this->dao = new UGroupDao();
        }
        return $this->dao;
    }

    /**
     * Wrapper for EventManager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        if (! $this->event_manager) {
            $this->event_manager = EventManager::instance();
        }
        return $this->event_manager;
    }

    /**
     * Get Dynamic ugroups members
     *
     * @param int $ugroupId Id of the ugroup
     * @param int $groupId Id of the project
     *
     * @return array of User
     */
    public function getDynamicUGroupsMembers($ugroupId, $groupId)
    {
        if ($ugroupId > 100) {
            return array();
        }
        $um = UserManager::instance();
        $users   = array();
        $dao     = $this->getUGroupUserDao();
        $members = $dao->searchUserByDynamicUGroupId($ugroupId, $groupId);
        if ($members && !$members->isError()) {
            foreach ($members as $member) {
                $users[] = $um->getUserById($member['user_id']);
            }
        }
        return $users;
    }

    /**
     * @param int $ugroup_id
     * @param int $group_id
     * @return bool
     */
    public function isDynamicUGroupMember(PFUSer $user, $ugroup_id, $group_id)
    {
        $dao = $this->getUGroupUserDao();

        return $dao->isDynamicUGroupMember($user->getId(), $ugroup_id, $group_id);
    }

    /**
     * Check if update users is allowed for a given user group
     *
     * @param int $ugroupId Id of the user group
     *
     * @return bool
     */
    public function isUpdateUsersAllowed($ugroupId)
    {
        $ugroupUpdateUsersAllowed = true;
        $this->getEventManager()->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));
        return $ugroupUpdateUsersAllowed;
    }

    /**
     * Wrapper for dao method that checks if the user group is valid
     *
     * @param int $groupId Id of the project
     * @param int $ugroupId Id of the user goup
     *
     * @return bool
     */
    public function checkUGroupValidityByGroupId($groupId, $ugroupId)
    {
        return $this->getDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Wrapper for dao method that retrieves all Ugroups bound to a given ProjectUGroup
     *
     * @param int $ugroupId Id of the user goup
     *
     * @return DataAccessResult
     */
    public function searchUGroupByBindingSource($ugroupId)
    {
        return $this->getDao()->searchUGroupByBindingSource($ugroupId);
    }

    /**
     * @return DataAccessResult
     */
    public function searchBindedUgroupsInProject(Project $project)
    {
        return $this->getDao()->searchBindedUgroupsInProject($project->getID());
    }

    /**
     * Wrapper for dao method that updates binding option for a given ProjectUGroup
     *
     * @param int $ugroup_id Id of the user group
     * @param int $source_ugroup_id Id of the user group we should bind to
     *
     * @return bool
     */
    public function updateUgroupBinding($ugroup_id, $source_ugroup_id = null)
    {
        $ugroup = $this->getById($ugroup_id);
        if ($source_ugroup_id === null) {
            $this->getEventManager()->processEvent(
                Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE,
                array(
                    'ugroup' => $ugroup
                )
            );
        } else {
            $source = $this->getById($source_ugroup_id);
            $this->getEventManager()->processEvent(
                Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD,
                array(
                    'ugroup' => $ugroup,
                    'source' => $source,
                )
            );
        }
        return $this->getDao()->updateUgroupBinding($ugroup_id, $source_ugroup_id);
    }

    /**
     * Wrapper to retrieve the source user group from a given bound ugroup id
     *
     * @param int $ugroupId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function getUgroupBindingSource($ugroupId)
    {
        $dar = $this->getDao()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new ProjectUGroup($dar->getRow());
        } else {
            return null;
        }
    }

    /**
     * Wrapper for UserGroupDao
     *
     * @return UserGroupDao
     */
    public function getUserGroupDao()
    {
        return new UserGroupDao();
    }

    /**
     * Return name and id of all ugroups belonging to a specific project
     *
     * @param int $groupId Id of the project
     * @param Array   $predefined List of predefined ugroup id
     *
     * @return DataAccessResult
     */
    public function getExistingUgroups($groupId, $predefined = null)
    {
        $dar = $this->getUserGroupDao()->getExistingUgroups($groupId, $predefined);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return array();
    }

    /**
     * @throws \Tuleap\Project\Admin\ProjectUGroup\CannotCreateUGroupException
     */
    public function createEmptyUgroup($project_id, $ugroup_name, $ugroup_description)
    {
        return ugroup_create($project_id, $ugroup_name, $ugroup_description, "cx_empty");
    }

    /**
     * @throws \Tuleap\Project\Admin\ProjectUGroup\CannotRemoveUserMembershipToUserGroupException
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     */
    public function syncUgroupMembers(ProjectUGroup $user_group, array $users_from_references)
    {
        $this->getDao()->startTransaction();

        $current_members   = $this->getUgroupMembers($user_group);
        $members_to_remove = $this->getUsersToRemove($current_members, $users_from_references);
        $members_to_add    = $this->getUsersToAdd($current_members, $users_from_references);

        foreach ($members_to_add as $member_to_add) {
            $this->addUserToUserGroup($user_group, $member_to_add);
        }

        foreach ($members_to_remove as $member_to_remove) {
            $this->removeUserFromUserGroup($user_group, $member_to_remove);
        }

        $this->getDao()->commit();
    }

    /**
     * @return array
     */
    private function getUgroupMembers(ProjectUGroup $user_group)
    {
        $members = array();

        foreach ($user_group->getMembersIncludingSuspendedAndDeleted() as $member) {
            $members[] = $member;
        }

        return $members;
    }

    private function getUsersToRemove(array $current_members, array $users_from_references)
    {
        return array_diff($current_members, $users_from_references);
    }

    private function getUsersToAdd(array $current_members, array $users_from_references)
    {
        return array_diff($users_from_references, $current_members);
    }

    /**
     * @throws UGroup_Invalid_Exception
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     */
    private function addUserToUserGroup(ProjectUGroup $user_group, PFUser $user)
    {
        switch ((int) $user_group->getId()) {
            case ProjectUGroup::PROJECT_MEMBERS:
                $this->getProjectMemberAdder()->addProjectMember($user, $user_group->getProject());
                break;
            case ProjectUGroup::PROJECT_ADMIN:
                $this->getDynamicUGroupMembersUpdater()->addUser($user_group->getProject(), $user_group, $user);
                break;
            default:
                $user_group->addUser($user);
        }
    }

    /**
     * @throws \Tuleap\Project\Admin\ProjectUGroup\CannotRemoveUserMembershipToUserGroupException
     */
    private function removeUserFromUserGroup(ProjectUGroup $user_group, PFUser $user)
    {
        switch ((int) $user_group->getId()) {
            case ProjectUGroup::PROJECT_MEMBERS:
                $this->getUserRemover()->removeUserFromProject($user_group->getProjectId(), $user->getId());
                break;
            case ProjectUGroup::PROJECT_ADMIN:
                $this->getDynamicUGroupMembersUpdater()->removeUser($user_group->getProject(), $user_group, $user);
                break;
            default:
                $user_group->removeUser($user);
        }
    }

    /**
     * @return UserRemover
     */
    private function getUserRemover()
    {
        return new UserRemover(
            ProjectManager::instance(),
            $this->getEventManager(),
            new ArtifactTypeFactory(false),
            new UserRemoverDao(),
            UserManager::instance(),
            new ProjectHistoryDao(),
            new UGroupManager()
        );
    }
}
