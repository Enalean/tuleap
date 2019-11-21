<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

require_once 'Docman_ItemFactory.class.php';

/**
 * This class is responsible to return authorized Ugroups for an item depending on its parents
 *
 */
class Docman_PermissionsItemManager
{
    public const PERMISSIONS_TYPE = 'PLUGIN_DOCMAN_%';

    private function mergeUgroupIds(array $parent_ugroups_ids, array $child_ugroups_ids)
    {
        $item_ugroups_ids   = array_intersect($parent_ugroups_ids, $child_ugroups_ids);
        $more_restrictive   = $this->getMoreRestrictiveUgroup($parent_ugroups_ids, $child_ugroups_ids);
        $remaining_ids      = array_diff($more_restrictive, $item_ugroups_ids);

        $contains_anonymous = $this->oneContainsAnonymous($child_ugroups_ids, $parent_ugroups_ids);

        foreach ($remaining_ids as $item_ugroup_id) {
            if ($item_ugroup_id < ProjectUGroup::NONE || $contains_anonymous) {
                $item_ugroups_ids[] = $item_ugroup_id;
            }
        }
        return array_unique($item_ugroups_ids);
    }

    private function getMoreRestrictiveUgroup($ugroups_ids1, $ugroups_ids2)
    {
        $ugroups_ids1_lowest = $this->lowest($ugroups_ids1);
        $ugroups_ids2_lowest = $this->lowest($ugroups_ids2);

        if ($ugroups_ids1_lowest == $ugroups_ids2_lowest) {
            return $this->getBiggestUgroupCollection($ugroups_ids1, $ugroups_ids2);
        }

        $ugroups_ids = $ugroups_ids1;
        if ($ugroups_ids1_lowest < $ugroups_ids2_lowest) {
            $ugroups_ids = $ugroups_ids2;
        }
        return $ugroups_ids;
    }

    private function getBiggestUgroupCollection($ugroups_ids1, $ugroups_ids2)
    {
        $ugroups_ids = $ugroups_ids1;
        if (count($ugroups_ids1) < count($ugroups_ids2)) {
            $ugroups_ids = $ugroups_ids2;
        }
        return $ugroups_ids;
    }

    private function oneContainsAnonymous($child_ugroups_ids, $parent_ugroups_ids)
    {
        return in_array(ProjectUGroup::ANONYMOUS, $child_ugroups_ids, true)
            || in_array(ProjectUGroup::ANONYMOUS, $parent_ugroups_ids, true);
    }

    private function lowest($array)
    {
        sort($array);
        return array_shift($array);
    }

    private function getUgroupIdsPermissions(Docman_Item $item, UGroupLiteralizer $literalizer, Project $project)
    {
        $ugroups_ids = $literalizer->getUgroupIds($project, $item->getId(), self::PERMISSIONS_TYPE);

        if (empty($ugroups_ids)) {
            $ugroups_ids = $this->getPermissionsManager()->getAuthorizedUgroupIds(
                $project->getID(),
                'PLUGIN_DOCMAN_ADMIN'
            );
        }

        $parent_item = $this->getParentItem($item, $project);
        if ($parent_item) {
            $parent_ugroups_ids = $this->getUgroupIdsPermissions($parent_item, $literalizer, $project);
            $ugroups_ids        = $this->mergeUgroupIds($parent_ugroups_ids, $ugroups_ids);
        }
        return array_values($ugroups_ids);
    }

    private function getParentItem(Docman_Item $item, Project $project)
    {
        if (! $item->getParentId()) {
            return;
        }
        return $this->getDocmanItemFactory($project)->getItemFromDb($item->getParentId());
    }

    /**
     * Returns ugroups of an item in a human readable format
     *
     * @param Docman_Item $item
     *
     * @return array
     */
    public function exportPermissions(Docman_Item $item)
    {
        $project     = $this->getProjectManager()->getProject($item->getGroupId());
        $literalizer = $this->getUGroupLiteralizer();
        $ugroup_ids  = $this->getUgroupIdsPermissions($item, $literalizer, $project);
        return $literalizer->ugroupIdsToString($ugroup_ids, $project);
    }

    // protected for testing purpose
    protected function getUGroupLiteralizer(): UGroupLiteralizer
    {
        return new UGroupLiteralizer();
    }

    // protected for testing purpose
    protected function getProjectManager(): ProjectManager
    {
        return ProjectManager::instance();
    }

    // protected for testing purpose
    protected function getPermissionsManager(): PermissionsManager
    {
        return PermissionsManager::instance();
    }

    // protected for testing purpose
    protected function getDocmanItemFactory(Project $project): Docman_ItemFactory
    {
        return Docman_ItemFactory::instance($project->getID());
    }
}
