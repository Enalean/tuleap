<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;

/**
 * This class do the mapping between Tuleap And Mediawiki groups
 */
class MediawikiUserGroupsMapper
{

    public const MEDIAWIKI_GROUPS_ANONYMOUS  = 'anonymous';
    public const MEDIAWIKI_GROUPS_USER       = 'user';
    public const MEDIAWIKI_GROUPS_BOT        = 'bot';
    public const MEDIAWIKI_GROUPS_SYSOP      = 'sysop';
    public const MEDIAWIKI_GROUPS_BUREAUCRAT = 'bureaucrat';

    public static $MEDIAWIKI_GROUPS_NAME = array (
        self::MEDIAWIKI_GROUPS_ANONYMOUS,
        self::MEDIAWIKI_GROUPS_USER,
        self::MEDIAWIKI_GROUPS_BOT,
        self::MEDIAWIKI_GROUPS_SYSOP,
        self::MEDIAWIKI_GROUPS_BUREAUCRAT
    );

    public static $MEDIAWIKI_MODIFIABLE_GROUP_NAMES = array(
        self::MEDIAWIKI_GROUPS_BOT,
        self::MEDIAWIKI_GROUPS_SYSOP,
        self::MEDIAWIKI_GROUPS_BUREAUCRAT,
    );

    public static $DEFAULT_MAPPING_PUBLIC_PROJECT = array (
        self::MEDIAWIKI_GROUPS_ANONYMOUS  => array(ProjectUGroup::ANONYMOUS),
        self::MEDIAWIKI_GROUPS_USER       => array(ProjectUGroup::REGISTERED),
        self::MEDIAWIKI_GROUPS_SYSOP      => array(ProjectUGroup::PROJECT_ADMIN),
        self::MEDIAWIKI_GROUPS_BUREAUCRAT => array(ProjectUGroup::PROJECT_ADMIN)
    );

    public static $DEFAULT_MAPPING_PRIVATE_PROJECT = array (
        self::MEDIAWIKI_GROUPS_USER       => array(ProjectUGroup::REGISTERED),
        self::MEDIAWIKI_GROUPS_SYSOP      => array(ProjectUGroup::PROJECT_ADMIN),
        self::MEDIAWIKI_GROUPS_BUREAUCRAT => array(ProjectUGroup::PROJECT_ADMIN)
    );

    /** @var MediawikiDao */
    private $dao;

    /** User_ForgeUserGroupPermissionsDao */
    private $forge_permissions_dao;

    public function __construct(MediawikiDao $dao, User_ForgeUserGroupPermissionsDao $forge_permissions_dao)
    {
        $this->dao = $dao;
        $this->forge_permissions_dao = $forge_permissions_dao;
    }

    /**
     *
     * @param array $new_mapping_list
     */
    public function saveMapping(array $new_mapping_list, Project $project)
    {
        $current_mapping_list = $this->getCurrentUserGroupMapping($project);
        $mappings_to_remove   = $this->getUserGroupMappingsDiff($current_mapping_list, $new_mapping_list);
        $mappings_to_add      = $this->getUserGroupMappingsDiff($new_mapping_list, $current_mapping_list);

        foreach (self::$MEDIAWIKI_MODIFIABLE_GROUP_NAMES as $mw_group_name) {
            $this->removeMediawikiUserGroupMapping($project, $mappings_to_remove, $mw_group_name);
            $this->addMediawikiUserGroupMapping($project, $mappings_to_add, $mw_group_name);
        }

        $this->dao->resetUserGroups($project);
    }

    private function getUserGroupMappingsDiff($group_mapping1, $group_mapping2)
    {
        $list = array();

        foreach (self::$MEDIAWIKI_MODIFIABLE_GROUP_NAMES as $mw_group_name) {
            if (!array_key_exists($mw_group_name, $group_mapping1)) {
                $group_mapping1[$mw_group_name] = array();
            }

            if (!array_key_exists($mw_group_name, $group_mapping2)) {
                $group_mapping2[$mw_group_name] = array();
            }

            $list[$mw_group_name] = array_diff($group_mapping1[$mw_group_name], $group_mapping2[$mw_group_name]);
        }
        return $list;
    }

    private function removeMediawikiUserGroupMapping(Project $project, array $mappings_to_remove, $mw_group_name)
    {
        foreach ($mappings_to_remove[$mw_group_name] as $ugroup_id) {
            $this->dao->removeMediawikiUserGroupMapping($project, $mw_group_name, $ugroup_id);
        }
    }

    private function addMediawikiUserGroupMapping(Project $project, array $mappings_to_add, $mw_group_name)
    {
        foreach ($mappings_to_add[$mw_group_name] as $ugroup_id) {
            $this->dao->addMediawikiUserGroupMapping($project, $mw_group_name, $ugroup_id);
        }
    }

    public function getCurrentUserGroupMapping($project)
    {
        $list = array();
        $data_result = $this->dao->getMediawikiUserGroupMapping($project);

        foreach (self::$MEDIAWIKI_GROUPS_NAME as $mw_group_name) {
            $list[$mw_group_name] = array();
            foreach ($data_result as $mapping) {
                if ($mapping['mw_group_name'] == $mw_group_name) {
                    $list[$mw_group_name][] = $mapping['ugroup_id'];
                }
            }
        }

        return $list;
    }

    public function isDefaultMapping(Project $project)
    {
        $current_mapping = $this->getCurrentUserGroupMapping($project);

        if ($project->isPublic()) {
            $default_mappings = self::$DEFAULT_MAPPING_PUBLIC_PROJECT;
        } else {
            $default_mappings = self::$DEFAULT_MAPPING_PRIVATE_PROJECT;
        }

        $added_groups   = $this->getUserGroupMappingsDiff($default_mappings, $current_mapping);
        $removed_groups = $this->getUserGroupMappingsDiff($current_mapping, $default_mappings);

        return $this->checkThereIsNoMappingsChanges($added_groups, $removed_groups);
    }

    private function checkThereIsNoMappingsChanges(array $added_groups, array $removed_groups)
    {
        foreach (self::$MEDIAWIKI_GROUPS_NAME as $group_name) {
            if (! (empty($added_groups[$group_name]) && empty($removed_groups[$group_name]))) {
                return false;
            }
        }

        return true;
    }

    public function getDefaultMappingsForProject(Project $project)
    {
        if ($project->isPublic()) {
            return self::$DEFAULT_MAPPING_PUBLIC_PROJECT;
        } else {
            return self::$DEFAULT_MAPPING_PRIVATE_PROJECT;
        }
    }

    public function defineUserMediawikiGroups(PFUser $user, Group $project)
    {
        $mediawiki_groups = new MediawikiGroups($this->dao->getMediawikiGroupsForUser($user, $project));
        $this->addGroupsAccordingToMapping($mediawiki_groups, $user, $project);
        return $mediawiki_groups->getAddedRemoved();
    }

    /**
     * This method will add missing permissions for a user
     *
     */
    private function addGroupsAccordingToMapping(MediawikiGroups $mediawiki_groups, PFUser $user, Group $project)
    {
        $mediawiki_groups->add('*');
        if ($user->isAnonymous()) {
            return;
        }

        if ($this->doesUserHaveSpecialAdminPermissions($user)) {
            $dar = $this->dao->getAllMediawikiGroups($project);
        } else {
            $dar = $this->dao->getMediawikiGroupsMappedForUGroups($user, $project);
        }

        foreach ($dar as $row) {
            $mediawiki_groups->add($row['real_name']);
        }
    }

    private function doesUserHaveSpecialAdminPermissions(PFUser $user)
    {
        return $this->forge_permissions_dao->doesUserHavePermission(
            $user->getId(),
            MediawikiAdminAllProjects::ID
        );
    }
}
