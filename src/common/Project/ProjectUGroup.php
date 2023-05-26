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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\User\UserGroup\NameTranslator;

/**
 * ProjectUGroup object
 */
class ProjectUGroup implements User_UGroup // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const NONE               = 100;
    public const ANONYMOUS          = 1;
    public const REGISTERED         = 2;
    public const AUTHENTICATED      = 5;
    public const PROJECT_MEMBERS    = 3;
    public const PROJECT_ADMIN      = 4;
    public const FILE_MANAGER_ADMIN = 11;
    public const DOCUMENT_TECH      = 12;
    public const DOCUMENT_ADMIN     = 13;
    public const WIKI_ADMIN         = 14;
    public const TRACKER_ADMIN      = 15;
    public const FORUM_ADMIN        = 16;
    public const NEWS_ADMIN         = 17;
    public const NEWS_WRITER        = 18;
    public const SVN_ADMIN          = 19;

    public const PROJECT_ADMIN_NAME   = 'project_admins';
    public const PROJECT_MEMBERS_NAME = 'project_members';

    public const WIKI_ADMIN_PERMISSIONS    = 'W2';
    public const PROJECT_ADMIN_PERMISSIONS = 'A';

    public const DYNAMIC_UPPER_BOUNDARY = 100;

    public const SYSTEM_USER_GROUPS = [
        self::NONE,
        self::ANONYMOUS,
        self::REGISTERED,
        self::AUTHENTICATED,
    ];

    public static $legacy_ugroups = [
        self::FILE_MANAGER_ADMIN,
        self::DOCUMENT_ADMIN,
        self::DOCUMENT_TECH,
        self::WIKI_ADMIN,
        self::TRACKER_ADMIN,
        self::FORUM_ADMIN,
        self::NEWS_ADMIN,
        self::NEWS_WRITER,
        self::SVN_ADMIN,
    ];

    public const NORMALIZED_NAMES = [
        self::NONE               => 'nobody',
        self::ANONYMOUS          => 'all_users',
        self::REGISTERED         => 'registered_users',
        self::AUTHENTICATED      => 'authenticated_users',
        self::PROJECT_MEMBERS    => self::PROJECT_MEMBERS_NAME,
        self::PROJECT_ADMIN      => self::PROJECT_ADMIN_NAME,
        self::FILE_MANAGER_ADMIN => 'file_manager_admins',
        self::DOCUMENT_TECH      => 'document_techs',
        self::DOCUMENT_ADMIN     => 'document_admins',
        self::WIKI_ADMIN         => 'wiki_admins',
        self::TRACKER_ADMIN      => 'tracker_admins',
        self::FORUM_ADMIN        => 'forum_admins',
        self::NEWS_ADMIN         => 'news_admins',
        self::NEWS_WRITER        => 'news_editors',
        self::SVN_ADMIN          => 'svn_admins',
    ];

    protected $id          = 0;
    protected $group_id    = 0;
    protected $name        = null;
    protected $description = null;
    protected $is_dynamic  = true;
    protected $source_id   = false;

    protected $members      = null;
    protected $members_name = null;
    /** @var Project|null */
    protected $project = null;
    /** @var self|null|false */
    protected $source_ugroup = false;

    /**
     * @var UGroupDao
     */
    private $ugroup_dao;
    /**
     * @var UGroupUserDao
     */
    private $ugroup_user_dao;

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
        if (! $this->ugroup_dao) {
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
        if (! $this->ugroup_user_dao) {
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
     * Get the ugroup name
     *
     * @return string
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
     */
    public function getNormalizedName()
    {
        if ($this->is_dynamic) {
            return self::NORMALIZED_NAMES[$this->id];
        }
        return $this->name;
    }

    /**
     * Get the ugroup id
     *
     * @psalm-mutation-free
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getProjectId()
    {
        return $this->group_id;
    }

    public function getProject()
    {
        if (! $this->project) {
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
        return \Tuleap\User\UserGroup\DescriptionTranslator::getUserGroupDisplayDescription((string) $this->getDescription());
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

    public function getMembersIncludingSuspendedAndDeleted()
    {
        if (! $this->members) {
            $this->members = $this->getStaticOrDynamicMembersIncludingSuspendedAndDeleted($this->group_id);
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
        $names = [];
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

    private function getStaticOrDynamicMembersIncludingSuspendedAndDeleted($group_id)
    {
        if ($this->is_dynamic) {
            $dar = $this->getUGroupUserDao()->searchUserByDynamicUGroupIdIncludingSuspendedAndDeleted($this->id, $group_id);
            return $dar->instanciateWith([$this, 'newUserFromIncompleteRow']);
        }
        $dar   = $this->getUGroupUserDao()->searchUserByStaticUGroupIdIncludingSuspendedAndDeleted($this->id);
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
            return [];
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
            $dar   = $this->getUGroupUserDao()->searchUserByDynamicUGroupIdIncludingSuspended($this->id, $group_id);
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
    * @param int $groupId The group id
    * @param int $ugroupId The ugroup id
    *
    * @return bool
    */
    public function exists($groupId, $ugroupId)
    {
        return $this->getUGroupDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Return project admins of given static group
     *
     * @param int $groupId Id of the project
     * @param array   $ugroups list of ugroups
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false
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
     * @return void
     * @throws UGroup_Invalid_Exception
     * @throws \Tuleap\Project\UGroups\Membership\InvalidProjectException
     * @throws \Tuleap\Project\UGroups\Membership\UserIsAnonymousException
     */
    public function addUser(PFUser $user, PFUser $project_admin)
    {
        $this->getMemberAdder()->addMember($user, $this, $project_admin);
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
        if (! $this->group_id) {
            throw new Exception('Invalid group_id');
        }
        if (! $this->id) {
            throw new UGroup_Invalid_Exception();
        }
        if ($user->isAnonymous()) {
            throw new Exception('Invalid user');
        }
    }

    private function getMemberAdder(): MemberAdder
    {
        return MemberAdder::build($this->getProjectMemberAdder());
    }

    private function getDynamicUGroupMembersUpdater(): DynamicUGroupMembersUpdater
    {
        return new DynamicUGroupMembersUpdater(
            new UserPermissionsDao(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $this->getProjectMemberAdder(),
            EventManager::instance(),
            new ProjectHistoryDao(),
        );
    }

    private function getProjectMemberAdder(): ProjectMemberAdder
    {
        return ProjectMemberAdderWithoutStatusCheckAndNotifications::build();
    }

    /**
     * Remove given user from user group
     * This method can remove from any group, either dynamic or static.
     *
     * @throws UGroup_Invalid_Exception
     */
    public function removeUser(PFUser $user, PFUser $project_administrator): void
    {
        $this->assertProjectUGroupAndUserValidity($user);
        if ($this->is_dynamic) {
            $this->removeUserFromDynamicGroup($user, $project_administrator);
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
     * @param int $group_id Id of the project
     * @param int $ugroup_id Id of the ugroup
     * @param int $user_id Id of the user
     *
     * @return void
     */
    protected function removeUserFromStaticGroup($group_id, $ugroup_id, $user_id)
    {
        include_once __DIR__ . '/../../www/project/admin/ugroup_utils.php';
        ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
    }

    protected function removeUserFromDynamicGroup(PFUser $user, PFUser $project_administrator): void
    {
        $project = $this->getProject();
        if ($project === null) {
            return;
        }

        $this->getDynamicUGroupMembersUpdater()->removeUser($project, $this, $user, $project_administrator);
    }

    /**
     * Check if the user group is bound
     *
     * @return bool
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

    public function setSourceGroup(?ProjectUGroup $ugroup = null)
    {
        $this->source_ugroup = $ugroup;
        $this->source_id     = ($ugroup === null) ? null : $ugroup->getId();
    }

    public function isStatic()
    {
        return $this->getId() > 100;
    }
}
