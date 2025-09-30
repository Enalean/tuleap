<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\User\UserGroup\NameTranslator;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class User_ForgeUserGroupFactory
{
    /**
     * @var UserGroupDao
     */
    private $dao;

    public function __construct(UserGroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return User_ForgeUGroup
     * @throws User_UserGroupNameInvalidException
     */
    public function createForgeUGroup($name, $description)
    {
        $user_group_id = $this->dao->createForgeUGroup($name, $description);

        if (! $user_group_id) {
            throw new User_UnableToCreateUserGroupException();
        }

        return new User_ForgeUGroup($user_group_id, $name, $description);
    }

    /**
     * @return User_ForgeUGroup
     * @throws User_UserGroupNotFoundException
     */
    public function getForgeUserGroupById($user_group_id)
    {
        $row = $this->dao->getForgeUGroup($user_group_id);
        if (! $row) {
            throw new User_UserGroupNotFoundException($user_group_id);
        }

        return new User_ForgeUGroup($user_group_id, $row['name'], $row['description']);
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getAllForgeUserGroups()
    {
        $user_groups = [];
        $rows        = $this->dao->getAllForgeUGroups();
        if (! $rows) {
            return $user_groups;
        }

        foreach ($rows as $row) {
            $user_groups[] = $this->instantiateFromRow($row);
        }

        return $user_groups;
    }

    /**
     * @return User_ForgeUGroup
     */
    public function instantiateFromRow($row)
    {
        return new User_ForgeUGroup($row['ugroup_id'], $row['name'], $row['description']);
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getAllForProject(Project $project): array
    {
        return array_merge(
            $this->getAllForProjectWithoutNobody($project),
            [$this->getDynamicForgeUserGroupByName(NameTranslator::NOBODY)]
        );
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getAllForProjectWithoutNobody(Project $project): array
    {
        $user_groups = [];

        if (ForgeConfig::areAnonymousAllowed() && $project->isPublic()) {
            $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::ANON);
        }

        return array_merge(
            $user_groups,
            $this->getAllForProjectWithoutNobodyNorAnonymous($project)
        );
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getAllForProjectWithoutNobodyNorAnonymous(Project $project): array
    {
        $user_groups = [];

        if (ForgeConfig::areRestrictedUsersAllowed() && $project->allowsRestricted()) {
            $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::AUTHENTICATED);
        }
        if ($project->isPublic()) {
            $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::REGISTERED);
        }
        $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::PROJECT_MEMBERS);
        $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::PROJECT_ADMINS);

        return array_merge(
            $user_groups,
            $this->getStaticByProject($project)
        );
    }

    public function getProjectUGroupsWithAdministratorAndMembers(Project $project)
    {
        $user_groups = [];

        $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::PROJECT_MEMBERS);
        $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::PROJECT_ADMINS);

        return array_merge($user_groups, $this->getStaticByProject($project), [$this->getDynamicForgeUserGroupByName(NameTranslator::NOBODY)]);
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getProjectUGroupsWithMembers(Project $project): array
    {
        return array_merge(
            $this->getProjectUGroupsWithMembersWithoutNobody($project),
            [$this->getDynamicForgeUserGroupByName(NameTranslator::NOBODY)]
        );
    }

    /**
     * @return User_ForgeUGroup[]
     */
    public function getProjectUGroupsWithMembersWithoutNobody(Project $project): array
    {
        $user_groups   = [];
        $user_groups[] = $this->getDynamicForgeUserGroupByName(NameTranslator::PROJECT_MEMBERS);

        return array_merge($user_groups, $this->getStaticByProject($project));
    }

    /**
     * @return User_ForgeUGroup
     */
    private function getDynamicForgeUserGroupByName($name)
    {
        $row = $this->dao->getDynamicForgeUserGroupByName($name);
        return $this->instantiateFromRow($row);
    }

    /**
     * @return User_ForgeUGroup[]
     */
    private function getStaticByProject(Project $project)
    {
        $user_groups = [];
        $rows        = $this->dao->getExistingUgroups($project->getID());

        if (! $rows) {
            return $user_groups;
        }

        foreach ($rows as $row) {
            $user_groups[] = $this->instantiateFromRow($row);
        }

        return $user_groups;
    }
}
