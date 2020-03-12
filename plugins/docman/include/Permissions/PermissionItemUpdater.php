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

namespace Tuleap\Docman\Permissions;

use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use EventManager;
use Feedback;
use PermissionsManager;
use PFUser;
use Tuleap\Docman\ResponseFeedbackWrapper;

/**
 * @psalm-type PermissionsPerUGroupIDAndType = array<int,key-of<self::PERMISSIONS_DEFINITIONS>>
 */
class PermissionItemUpdater
{
    public const PERMISSION_DEFINITION_READ   = 1;
    public const PERMISSION_DEFINITION_WRITE  = 2;
    public const PERMISSION_DEFINITION_MANAGE = 3;
    public const PERMISSION_DEFINITION_NONE   = 100;
    private const PERMISSIONS_DEFINITIONS = [
        self::PERMISSION_DEFINITION_READ   => [
            'order' => 1,
            'type'  => Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ,
        ],
        self::PERMISSION_DEFINITION_WRITE  => [
            'order' => 2,
            'type'  => Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE,
        ],
        self::PERMISSION_DEFINITION_MANAGE => [
            'order' => 3,
            'type'  => Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE,
        ],
        self::PERMISSION_DEFINITION_NONE => [
            'order' => 0,
            'type'  => null
        ],
    ];
    /**
     * @var ResponseFeedbackWrapper
     */
    private $response_feedback_wrapper;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var Docman_PermissionsManager
     */
    private $docman_permissions_manager;
    /**
     * @var PermissionsManager
     */
    private $global_permissions_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        ResponseFeedbackWrapper $response_feedback_wrapper,
        Docman_ItemFactory $item_factory,
        Docman_PermissionsManager $docman_permissions_manager,
        PermissionsManager $global_permissions_manager,
        EventManager $event_manager
    ) {
        $this->response_feedback_wrapper  = $response_feedback_wrapper;
        $this->item_factory               = $item_factory;
        $this->docman_permissions_manager = $docman_permissions_manager;
        $this->global_permissions_manager = $global_permissions_manager;
        $this->event_manager              = $event_manager;
    }

    /**
     * @psalm-param PermissionsPerUGroupIDAndType $permissions
     */
    public function initPermissionsOnNewlyCreatedItem(Docman_Item $item, array $permissions) : void
    {
        $this->setPermissions($item, true, $permissions);
    }

    /**
     * @psalm-param PermissionsPerUGroupIDAndType $permissions
     */
    public function updateItemPermissions(Docman_Item $item, PFUser $user, array $permissions) : void
    {
        $this->setPermissions($item, false, $permissions);

        $this->event_manager->processEvent(
            'plugin_docman_event_perms_change',
            array(
                'group_id' => $item->getGroupId(),
                'item'     => $item,
                'user'     => $user,
            )
        );
    }

    /**
     * @psalm-param PermissionsPerUGroupIDAndType $permissions
     */
    public function updateFolderAndChildrenPermissions(Docman_Folder $folder, PFUser $user, array $permissions) : void
    {
        $this->updateItemPermissions($folder, $user, $permissions);

        // clone permissions for sub items
        // Recursive application via a callback of Docman_Actions::recursivePermissions in
        // Docman_ItemFactory::breathFirst
        $this->item_factory->breathFirst(
            $folder->getId(),
            /**
             * @psalm-param array{item_id:int,title:string} $data
             */
            function (array $data) use ($user, $folder) : void {
                $inspected_item_id = $data['item_id'];
                if ($this->docman_permissions_manager->userCanManage($user, $inspected_item_id)) {
                    $this->global_permissions_manager->clonePermissions(
                        $folder->getId(),
                        $inspected_item_id,
                        Docman_PermissionsManager::ITEM_PERMISSION_TYPES
                    );
                } else {
                    $this->response_feedback_wrapper->log(
                        Feedback::WARN,
                        sprintf(dgettext('tuleap-docman', 'you cannot change permissions for sub-item %1$s since you do not have sufficient permissions.'), $data['title'])
                    );
                }
            },
            []
        );
        $this->response_feedback_wrapper->log(Feedback::INFO, dgettext('tuleap-docman', 'Permissions for sub-items successfully updated.'));
    }

    /**
     * @param bool $force true if you want to bypass permissions checking (@see permission_add_ugroup)
     * @psalm-param PermissionsPerUGroupIDAndType $permissions
     */
    private function setPermissions(Docman_Item $item, bool $force, array $permissions) : void
    {
        $old_permissions = permission_get_ugroups_permissions(
            $item->getGroupId(),
            $item->getId(),
            Docman_PermissionsManager::ITEM_PERMISSION_TYPES,
            false
        );
        $done_permissions = [];
        $history          = [
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ => false,
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE => false,
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE => false
        ];

        foreach ($permissions as $ugroup_id => $wanted_permission) {
            $this->setPermission(
                (int) $item->getGroupId(),
                (int) $item->getId(),
                $old_permissions,
                $done_permissions,
                (int) $ugroup_id,
                $wanted_permission,
                $history,
                $force
            );
        }

        foreach ($history as $perm => $put_in_history) {
            if ($put_in_history) {
                permission_add_history($item->getGroupId(), $perm, $item->getId());
            }
        }

        $this->response_feedback_wrapper->log(Feedback::INFO, dgettext('tuleap-docman', 'Permissions successfully updated.'));
    }

    /**
     * Set the permission for a ugroup on an item.
     *
     * The difficult part of the algorithm comes from two point:
     * - There is a hierarchy between ugroups (@see ugroup_get_parent)
     * - There is a hierarchy between permissions (READ < WRITE < MANAGE)
     *
     * Let's see a scenario:
     * I've selected WRITE permission for Registered users and READ permission for Project Members
     * => Project Members ARE registered users therefore they have WRITE permission.
     * => WRITE is stronger than READ permission.
     * So the permissions wich will be set are: WRITE for registered and WRITE for project members
     *
     * The force parameter must be set to true if you want to bypass permissions checking (@see permission_add_ugroup).
     * Pretty difficult to know if a user can update the permissions which does not exist for a new item...
     *
     * @param int $group_id integer The id of the project
     * @param int $item_id integer The id of the item
     * @param array $old_permissions The permissions before
     * @param array &$done_permissions The permissions after
     * @param int $ugroup_id ugroup_id we want to set permission now
     * @param int $wanted_permission The permissions the user has asked
     * @param array &$history array Does a permission has been set ?
     *
     * @psalm-param key-of<self::PERMISSIONS_DEFINITIONS> $wanted_permission
     * @psalm-param array<value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES>,bool> $history
     * @param-out array<"PLUGIN_DOCMAN_MANAGE"|"PLUGIN_DOCMAN_READ"|"PLUGIN_DOCMAN_WRITE"|value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES>, bool> $history
     *
     * @access protected
     */
    private function setPermission(
        int $group_id,
        int $item_id,
        array $old_permissions,
        array &$done_permissions,
        int $ugroup_id,
        int $wanted_permission,
        array &$history,
        bool $force
    ) {
        //Do nothing if we have already choose a permission for ugroup
        if (! isset($done_permissions[$ugroup_id])) {
            //if the ugroup has a parent
            if (($parent = ugroup_get_parent($ugroup_id)) !== false) {
                //first choose the permission for the parent
                $this->setPermission(
                    $group_id,
                    $item_id,
                    $old_permissions,
                    $done_permissions,
                    (int) $parent,
                    $wanted_permission,
                    $history,
                    $force
                );

                //is there a conflict between given permissions?
                if ($parent = $this->getBiggerOrEqualParent($done_permissions, (int) $parent, $wanted_permission)) {
                    //warn the user that there was a conflict
                    $this->response_feedback_wrapper->log(
                        Feedback::WARN,
                        sprintf(dgettext('tuleap-docman', 'Permissions for %1$s has been ignored because %2$s is %3$s.'), $old_permissions[$ugroup_id]['ugroup']['name'], $old_permissions[$parent]['ugroup']['name'], permission_get_name(self::PERMISSIONS_DEFINITIONS[$done_permissions[$parent]]['type']))
                    );

                    //remove permissions which was set for the ugroup
                    if (count($old_permissions[$ugroup_id]['permissions'])) {
                        /** @psalm-var value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES> $permission */
                        foreach ($old_permissions[$ugroup_id]['permissions'] as $permission => $nop) {
                            permission_clear_ugroup_object($group_id, $permission, $ugroup_id, $item_id);
                            $history[$permission] = true;
                        }
                    }

                    //The permission is none (default) for this ugroup
                    $done_permissions[$ugroup_id] = 100;
                }
            }

            //If the permissions have not been set (no parent || no conflict)
            if (! isset($done_permissions[$ugroup_id])) {
                //remove permissions if needed
                $perms_cleared          = false;
                $old_ugroup_permissions = $old_permissions[$ugroup_id]['permissions'] ?? [];
                /** @psalm-var value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES> $permission */
                foreach ($old_ugroup_permissions as $permission => $nop) {
                    if ($permission != self::PERMISSIONS_DEFINITIONS[$wanted_permission]['type']) {
                        //The permission has been changed
                        permission_clear_ugroup_object($group_id, $permission, $ugroup_id, $item_id);
                        $history[$permission] = true;
                        $perms_cleared = true;
                        $done_permissions[$ugroup_id] = 100;
                    } else {
                        //keep the old permission
                        $done_permissions[$ugroup_id] = Docman_PermissionsManager::getDefinitionIndexForPermission($permission);
                    }
                }

                //If the user set an explicit permission and there was no perms before or they have been removed
                if ($wanted_permission != 100 && (!count($old_permissions[$ugroup_id]['permissions']) || $perms_cleared)) {
                    //Then give the permission
                    if (isset(self::PERMISSIONS_DEFINITIONS[$wanted_permission]['type'])) {
                        /** @psalm-var value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES> $permission */
                        $permission = self::PERMISSIONS_DEFINITIONS[$wanted_permission]['type'];
                        permission_add_ugroup($group_id, $permission, $item_id, $ugroup_id, $force);

                        $history[$permission]         = true;
                        $done_permissions[$ugroup_id] = $wanted_permission;
                    }
                } else {
                    //else set none(default) permission
                    $done_permissions[$ugroup_id] = 100;
                }
            }
        }
    }

    /**
     * Return the parent (or grand parent) of ugroup $parent which has a bigger permission
     *
     * @return int|false the ugroup id which has been found or false
     */
    private function getBiggerOrEqualParent(array $done_permissions, int $parent, int $wanted_permission)
    {
        //No need to search for parent if the wanted permission is the default one
        if ($wanted_permission == 100) {
            return false;
        } else {
            //If the parent permission is bigger than the wanted permission
            if (self::PERMISSIONS_DEFINITIONS[$done_permissions[$parent]]['order'] >= self::PERMISSIONS_DEFINITIONS[$wanted_permission]['order']) {
                //then return parent
                return $parent;
            } else {
                //else compare with grand parents (recursively)
                if (($parent = ugroup_get_parent($parent)) !== false) {
                    return $this->getBiggerOrEqualParent($done_permissions, (int) $parent, $wanted_permission);
                } else {
                    return false;
                }
            }
        }
    }
}
