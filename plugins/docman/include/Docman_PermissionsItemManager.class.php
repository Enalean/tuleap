<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/project/UGroupLiteralizer.class.php';
require_once 'common/project/ProjectManager.class.php';
require_once 'Docman_ItemFactory.class.php';

class Docman_PermissionsItemManager {

    const PERMISSIONS_TYPE = 'PLUGIN_DOCMAN_%';

    private function getParentItem(Docman_Item $item, Project $project) {
        if (! $item->getParentId()) return;
        return Docman_ItemFactory::instance($project->getID())->getItemFromDb($item->getParentId());
    }

    private function mergeUgroupIds(array $parent_permissions, array $child_permissions) {
        $item_permissions = array();
        foreach($child_permissions as $child_permission) {
            if ($child_permission < 100) {
                foreach ($parent_permissions as $parent_permission) {
                    $item_permissions[] = max($parent_permission, $child_permission);
                }
            } elseif(in_array($child_permission, $parent_permissions)) {
                $item_permissions[] = $child_permission;
            }
        }
        return array_unique($item_permissions);
    }

    private function getUgroupIdsPermissions(Docman_Item $item, UGroupLiteralizer $literalizer, Project $project) {
        $permissions = $literalizer->getUgroupIds($item->getId(), self::PERMISSIONS_TYPE);
        $parent_item = $this->getParentItem($item, $project);
        if ($parent_item) {
            $parent_permissions = $this->getUgroupIdsPermissions($parent_item, $literalizer, $project);
            $permissions        = $this->mergeUgroupIds($parent_permissions, $permissions);
        }
        return array_values($permissions);
    }



    /**
     * Returns permissions of an item in a human readable format
     *
     * @param Docman_Item $item
     *
     * @return array
     */
    public function exportPermissions(Docman_Item $item) {
        $project     = ProjectManager::instance()->getProject($item->getGroupId());
        $literalizer = new UGroupLiteralizer();
        $ugroup_ids  = $this->getUgroupIdsPermissions($item, $literalizer, $project);
        return $literalizer->ugroupIdsToString($ugroup_ids, $project);
    }
}

?>
