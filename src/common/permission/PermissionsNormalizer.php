<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PermissionsNormalizer
{

    public function getNormalizedUGroupIds(Project $project, array $ugroup_ids, PermissionsNormalizerOverrideCollection $override_collection)
    {
        $ugroup_mapper = new PermissionsUGroupMapper($project);
        $normalized_ugroup_ids = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $this->appendOnceToHash(
                $normalized_ugroup_ids,
                $ugroup_mapper->getUGroupIdAccordingToMapping($ugroup_id)
            );
        }

        return $this->filterCatchAllGroups($normalized_ugroup_ids, $override_collection);
    }

    private function appendOnceToHash(array &$array, $id)
    {
        if (! isset($array[$id])) {
            $array[$id] = $id;
        }
    }

    private function filterCatchAllGroups(array $normalized_ugroup_ids, PermissionsNormalizerOverrideCollection $override_collection)
    {
        $ugroup_ids = $this->filterPlatformCatchAllGroup($normalized_ugroup_ids, $override_collection);
        if ($ugroup_ids === null) {
            $ugroup_ids = $this->filterProjectCatchAllGroup($normalized_ugroup_ids, $override_collection);
        }
        return $ugroup_ids;
    }

    private function filterPlatformCatchAllGroup(array $normalized_ugroup_ids, PermissionsNormalizerOverrideCollection $override_collection)
    {
        $catch_all_groups = [ProjectUGroup::ANONYMOUS, ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED];
        foreach ($catch_all_groups as $catch_all_ugroup_id) {
            if (isset($normalized_ugroup_ids[$catch_all_ugroup_id])) {
                $override_collection->addArrayOverrideBy($normalized_ugroup_ids, $catch_all_ugroup_id);
                return [$catch_all_ugroup_id];
            }
        }
        return null;
    }

    private function filterProjectCatchAllGroup(array $normalized_ugroup_ids, PermissionsNormalizerOverrideCollection $override_collection)
    {
        $final = [];
        foreach ($normalized_ugroup_ids as $ugroup_id) {
            if ($this->isProjectCatchAll($ugroup_id, $normalized_ugroup_ids)) {
                $override_collection->addOverrideBy($ugroup_id, ProjectUGroup::PROJECT_MEMBERS);
                continue;
            }
            $final[$ugroup_id] = $ugroup_id;
        }
        return $final;
    }

    private function isProjectCatchAll($ugroup_id, array $normalized_ugroup_ids)
    {
        return $ugroup_id < ProjectUGroup::DYNAMIC_UPPER_BOUNDARY &&
               isset($normalized_ugroup_ids[ProjectUGroup::PROJECT_MEMBERS]) &&
               $ugroup_id != ProjectUGroup::PROJECT_MEMBERS;
    }
}
