<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\Admin\ProjectWithoutRestrictedFeatureFlag;
use Tuleap\User\UserGroup\NameTranslator;

/**
 * ProjectUGroup object
 */
class ProjectUGroup implements User_UGroup // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    const NONE               = 100;
    const ANONYMOUS          = 1;
    const REGISTERED         = 2;
    const AUTHENTICATED      = 5;
    const PROJECT_MEMBERS    = 3;
    const PROJECT_ADMIN      = 4;
    const FILE_MANAGER_ADMIN = 11;
    const DOCUMENT_TECH      = 12;
    const DOCUMENT_ADMIN     = 13;
    const WIKI_ADMIN         = 14;
    const TRACKER_ADMIN      = 15;
    const FORUM_ADMIN        = 16;
    const NEWS_ADMIN         = 17;
    const NEWS_WRITER        = 18;
    const SVN_ADMIN          = 19;

    const WIKI_ADMIN_PERMISSIONS    = 'W2';
    const PROJECT_ADMIN_PERMISSIONS = 'A';

    const DYNAMIC_UPPER_BOUNDARY = 100;

    public static $legacy_ugroups = array(
        self::FILE_MANAGER_ADMIN,
        self::DOCUMENT_ADMIN,
        self::DOCUMENT_TECH,
        self::WIKI_ADMIN,
        self::TRACKER_ADMIN,
        self::FORUM_ADMIN,
        self::NEWS_ADMIN,
        self::NEWS_WRITER,
        self::SVN_ADMIN,
    );

    public static $forge_user_groups = array(
        self::NONE,
        self::ANONYMOUS,
        self::REGISTERED,
        self::AUTHENTICATED,
    );

    public static $normalized_names = array(
        self::NONE               => 'nobody',
        self::ANONYMOUS          => 'all_users',
        self::REGISTERED         => 'registered_users',
        self::AUTHENTICATED      => 'authenticated_users',
        self::PROJECT_MEMBERS    => 'project_members',
        self::PROJECT_ADMIN      => 'project_admins',
        self::FILE_MANAGER_ADMIN => 'file_manager_admins',
        self::DOCUMENT_TECH      => 'document_techs',
        self::DOCUMENT_ADMIN     => 'document_admins',
        self::WIKI_ADMIN         => 'wiki_admins',
        self::TRACKER_ADMIN      => 'tracker_admins',
        self::FORUM_ADMIN        => 'forum_admins',
        self::NEWS_ADMIN         => 'news_admins',
        self::NEWS_WRITER        => 'news_editors',
        self::SVN_ADMIN          => 'svn_admins',
    );

    protected $id    = 0;
    protected $group_id     = 0;
    protected $name         = null;
    protected $description  = null;
    protected $is_dynamic   = true;
    protected $source_id    = false;

    protected $members      = null;
    protected $members_name = null;
    /** @var Project|null */
    protected $project      = null;
    /** @var self|null|false */
    protected $source_ugroup  = false;

    /**
     * @var UGroupDao
     */
    private $ugroup_dao;
    /**
     * @var UGroupUserDao
     */
    private $ugroup_user_dao;
    /**
     * @var UserGroupDao
     */
    private $user_group_dao;

    /**
     * Constructor of the class
     *
     * @param array $row ugroup row
     *
     * @return Void
     */
    public function __construct($row = null)
    {
        $this->id          = isset($row['ugroup_id'])   ? $row['ugroup_id']   : 0;
        $this->name        = isset($row['name'])        ? $row['name']        : null;
        $this->description = isset($row['description']) ? $row['description'] : null;
        $this->group_id    = isset($row['group_id'])    ? $row['group_id']    : 0;
        $this->source_id   = isset($row['source_id'])   ? $row['source_id']   : false;
        $this->is_dynamic  = $this->id < 100;
    }

    /**
     * Get instance of UGroupDao
     *
     * @return UGroupDao
     */
    protected function getUGroupDao()
    {
        if (!$this->ugroup_dao) {
            $this->ugroup_dao = new UGroupDao();
        }
        return $this->ugroup_dao;
    }

    /**
     * Get instance of UGroupUserDao
     *
     * @return UGroupUserDao
     */
    protected function getUGroupUserDao()
    {
        if (!$this->ugroup_user_dao) {
            $this->ugroup_user_dao = new UGroupUserDao();
        }
        return $this->ugroup_user_dao;
    }

    public function setUGroupUserDao(UGroupUserDao $dao)
    {
        $this->ugroup_user_dao = $dao;
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    public function setProjectId($project_id)
    {
        $this->group_id = $project_id;
    }

    /**
     * Get instance of UserGroupDao
     *
     * @return UserGroupDao
     */
    protected function getUserGroupDao()
    {
        if (!$this->user_group_dao) {
            $this->user_group_dao = new UserGroupDao();
        }
        return $this->user_group_dao;
    }

    /**
     * Get the ugroup name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getTranslatedName()
    {
        return NameTranslator::getUserGroupDisplayName($this->name);
    }

    /**
     * return the string identifier for dynamic user groups
     * or the name of static user groups
     *
     * @return string
     */
    public function getNormalizedName()
    {
        if ($this->is_dynamic) {
            return self::$normalized_names[$this->id];
        }
        return $this->name;
    }


    /**
     * Get the ugroup id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getProjectId()
    {
        return $this->group_id;
    }

    public function getProject()
    {
        if (!$this->project) {
            $this->project = ProjectManager::instance()->getProject($this->group_id);
        }
        return $this->project;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getTranslatedDescription()
    {
        return util_translate_desc_ugroup($this->getDescription());
    }

    /**
     * Return array of users members of the ugroup
     *
     * @return PFUser[]
     */
    public function getMembers()
    {
        if (! $this->members) {
            $this->members = $this->getStaticOrDynamicMembers($this->group_id);
        }
        return $this->members;
    }

    public function getMembersIncludingSuspended()
    {
        if (! $this->members) {
            $this->members = $this->getStaticOrDynamicMembersInludingSuspended($this->group_id);
        }
        return $this->members;
    }

    /**
     *
     * @return Users
     */
    public function getUsers()
    {
        return new Users(...$this->getStaticOrDynamicMembers($this->group_id));
    }

    /**
     * Return array containing the user_name of all ugroup members
     *
     * @return array
     */
    public function getMembersUserName()
    {
        $names = array();
        foreach ($this->getMembers() as $member) {
            $names[] = $member->getUserName();
        }
        return $names;
    }

    private function getStaticOrDynamicMembers($group_id)
    {
        if ($this->is_dynamic) {
            $dar   = $this->getUGroupUserDao()->searchUserByDynamicUGroupId($this->id, $group_id);
            $users = [];
            foreach ($dar as $row) {
                $user = $this->newUserFromIncompleteRow($row);
                if ($user !== null) {
                    $users[] = $user;
                }
            }
            return $users;
        }
        $dar   = $this->getUGroupUserDao()->searchUserByStaticUGroupId($this->id);
        $users = [];
        foreach ($dar as $row) {
            $users[] = $this->newUser($row);
        }

        return $users;
    }

    private function getStaticOrDynamicMembersInludingSuspended($group_id)
    {
        if ($this->is_dynamic) {
            $dar = $this->getUGroupUserDao()->searchUserByDynamicUGroupIdIncludingSuspended($this->id, $group_id);
            return $dar->instanciateWith(array($this, 'newUserFromIncompleteRow'));
        }
        $dar   = $this->getUGroupUserDao()->searchUserByStaticUGroupIdIncludingSuspended($this->id);
        $users = [];
        foreach ($dar as $row) {
            $users[] = $this->newUser($row);
        }

        return $users;
    }

    /**
     * Return array of users members of the ugroup
     *
     * @return PFUser[]
     */
    public function getStaticOrDynamicMembersPaginated($project_id, $limit, $offset)
    {
        if ($this->is_dynamic) {
            return $this->getDynamicMembersPaginated($project_id, $limit, $offset);
        }

        return $this->getStaticMembersPaginated($limit, $offset);
    }

    /**
     * Get the members of a dynamic group
     *
     * @return PFUser[]
     */
    private function getDynamicMembersPaginated($group_id, $limit, $offset)
    {
        $dar = $this->getUGroupUserDao()->searchUsersByDynamicUGroupIdPaginated($this->id, $group_id, $limit, $offset);

        if (! $dar) {
            return array();
        }

        $users = [];
        foreach ($dar as $row) {
            $user = $this->newUserFromIncompleteRow($row);
            if ($user !== null) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Get the members of a static group
     *
     * @return PFUser[]
     */
    private function getStaticMembersPaginated($limit, $offset)
    {
        $users = [];
        foreach ($this->getUGroupUserDao()->searchUsersByStaticUGroupIdPaginated($this->id, $limit, $offset) as $row) {
            $users[] = $this->newUser($row);
        }
        return $users;
    }

    /**
     * Count the number of users in a ugroup
     *
     * @return int
     */
    public function countStaticOrDynamicMembers($group_id)
    {
        $count = 0;

        if ($this->is_dynamic) {
            $dar   = $this->getUGroupUserDao()->searchUserByDynamicUGroupId($this->id, $group_id);
            $count = $dar->count();
        } else {
            $dar   = $this->getUGroupUserDao()->countUserByStaticUGroupId($this->id)->getRow();
            $count = $dar['count_users'];
        }

        return $count;
    }

    public function newUser($row)
    {
        return new PFUser($row);
    }

    public function newUserFromIncompleteRow($row)
    {
        return UserManager::instance()->getUserById($row['user_id']);
    }

    /**
    * Check if the ugroup exist for the given project
    *
    * @param integer $groupId  The group id
    * @param integer $ugroupId The ugroup id
    *
    * @return boolean
    */
    public function exists($groupId, $ugroupId)
    {
        return $this->getUGroupDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Return project admins of given static group
     *
     * @param integer $groupId Id of the project
     * @param array   $ugroups list of ugroups
     *
     * @return DataAccessResult|false
     */
    public function returnProjectAdminsByStaticUGroupId($groupId, $ugroups)
    {
        return $this->getUGroupUserDao()->returnProjectAdminsByStaticUGroupId($groupId, $ugroups);
    }

    /**
     * Add the given user to the group
     * This method can add to any group, either dynamic or static.
     *
     * @param PFUser $user User to add
     *
     * @throws UGroup_Invalid_Exception
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     *
     * @return void
     */
    public function addUser(PFUser $user)
    {
        $this->assertProjectUGroupAndUserValidity($user);
        $project = $this->getProject();
        if ($project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED && ForgeConfig::areRestrictedUsersAllowed() &&
            ProjectWithoutRestrictedFeatureFlag::isEnabled() && $user->isRestricted()) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }
        if ($this->is_dynamic) {
            $this->addUserToDynamicGroup($user);
        } else {
            if ($this->exists($this->group_id, $this->id)) {
                $this->addUserToStaticGroup($this->group_id, $this->id, $user->getId());
            } else {
                throw new UGroup_Invalid_Exception();
            }
        }
    }

    /**
     * Test the status of the ugroup & the user
     *
     * @param PFUser $user User to test
     *
     * @return void
     */
    private function assertProjectUGroupAndUserValidity($user)
    {
        if (!$this->group_id) {
            throw new Exception('Invalid group_id');
        }
        if (!$this->id) {
            throw new UGroup_Invalid_Exception();
        }
        if ($user->isAnonymous()) {
            throw new Exception('Invalid user');
        }
    }

    /**
     * Add user to a static ugroup
     *
     * @param integer $group_id  Id of the project
     * @param integer $ugroup_id Id of the ugroup
     * @param integer $user_id   Id of the user
     *
     * @return void
     */
    protected function addUserToStaticGroup($group_id, $ugroup_id, $user_id)
    {
        include_once 'www/project/admin/ugroup_utils.php';
        ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id);
    }

    /**
     * Add user to a dynamic ugroup
     *
     * @param PFUser $user User to add
     *
     * @return Void
     */
    protected function addUserToDynamicGroup(PFUser $user)
    {
        $dao  = $this->getUserGroupDao();
        $flag = $this->getAddFlagForUGroupId($this->id);
        $dao->updateUserGroupFlags($user->getId(), $this->group_id, $flag);
    }

    /**
     * Convert a dynamic ugroup_id into it's DB table update to add someone to a given group
     *
     * @param integer $id Id of the ugroup
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return string
     */
    public function getAddFlagForUGroupId($id)
    {
        switch ($id) {
            case self::PROJECT_ADMIN:
                return "admin_flags = 'A'";
            case self::FILE_MANAGER_ADMIN:
                return 'file_flags = 2';
            case self::WIKI_ADMIN:
                return 'wiki_flags = 2';
            case self::SVN_ADMIN:
                return 'svn_flags = 2';
            case self::FORUM_ADMIN:
                return 'forum_flags = 2';
            case self::NEWS_ADMIN:
                return 'news_flags = 2';
            case self::NEWS_WRITER:
                return 'news_flags = 1';
            default:
                throw new UGroup_Invalid_Exception();
        }
    }

    /**
     * Remove given user from user group
     * This method can remove from any group, either dynamic or static.
     *
     * @param PFUser $user
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return void
     */
    public function removeUser(PFUser $user)
    {
        $this->assertProjectUGroupAndUserValidity($user);
        if ($this->is_dynamic) {
            $this->removeUserFromDynamicGroup($user);
        } else {
            if ($this->exists($this->group_id, $this->id)) {
                $this->removeUserFromStaticGroup($this->group_id, $this->id, $user->getId());
            } else {
                throw new UGroup_Invalid_Exception();
            }
        }
    }

    /**
     * Remove user from static ugroup
     *
     * @param integer $group_id  Id of the project
     * @param integer $ugroup_id Id of the ugroup
     * @param integer $user_id   Id of the user
     *
     * @return void
     */
    protected function removeUserFromStaticGroup($group_id, $ugroup_id, $user_id)
    {
        include_once 'www/project/admin/ugroup_utils.php';
        ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
    }

    /**
     * Remove user from dynamic ugroup
     *
     * @param PFUser $user User to remove
     *
     * @return boolean
     */
    protected function removeUserFromDynamicGroup(PFUser $user)
    {
        $dao  = $this->getUserGroupDao();
        if ($this->id == self::PROJECT_ADMIN &&
            $dao->searchProjectAdminsByProjectIdExcludingOneUserId($this->group_id, $user->getId())->rowCount() === 0) {
            throw new Exception('Impossible to remove last admin of the project');
        }
        $flag = $this->getRemoveFlagForUGroupId($this->id);
        return $dao->updateUserGroupFlags($user->getId(), $this->group_id, $flag);
    }

    /**
     * Convert a dynamic ugroup_id into it's DB table update to remove someone from given group
     *
     * @param integer $id Id of the ugroup
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return string
     */
    public function getRemoveFlagForUGroupId($id)
    {
        switch ($id) {
            case self::PROJECT_ADMIN:
                return "admin_flags = ''";
            case self::FILE_MANAGER_ADMIN:
                return 'file_flags = 0';
            case self::WIKI_ADMIN:
                return 'wiki_flags = 0';
            case self::SVN_ADMIN:
                return 'svn_flags = 0';
            case self::FORUM_ADMIN:
                return 'forum_flags = 0';
            case self::NEWS_ADMIN:
            case self::NEWS_WRITER:
                return 'news_flags = 0';
            default:
                throw new UGroup_Invalid_Exception();
        }
    }

    /**
     * Check if the user group is bound
     *
     * @param integer $ugroupId Id of the user goup
     *
     * @return boolean
     */
    public function isBound()
    {
        if ($this->source_id === false) {
            $this->getSourceGroup();
        }
        return ($this->source_id != null);
    }

    public function getSourceGroup()
    {
        if ($this->source_ugroup === false) {
            $this->setSourceGroup($this->getUgroupBindingSource($this->id));
        }
        return $this->source_ugroup;
    }

    protected function getUgroupBindingSource($id)
    {
        $ugroup_manager = new UGroupManager();
        return $ugroup_manager->getUgroupBindingSource($id);
    }

    public function setSourceGroup(ProjectUGroup $ugroup = null)
    {
        $this->source_ugroup = $ugroup;
        $this->source_id = ($ugroup === null) ? null : $ugroup->getId();
    }

    public function isStatic()
    {
        return $this->getId() > 100;
    }
}
