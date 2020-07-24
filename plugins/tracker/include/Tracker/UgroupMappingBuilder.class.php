<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * I build a ugroup mapping
 */
class Tracker_UgroupMappingBuilder
{

    /** @var Tracker_UgroupPermissionsGoldenRetriever */
    private $permissions_retriever;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(
        Tracker_UgroupPermissionsGoldenRetriever $permissions_retriever,
        UGroupManager $ugroup_manager
    ) {
        $this->permissions_retriever = $permissions_retriever;
        $this->ugroup_manager        = $ugroup_manager;
    }

    /**
     * @return int[] array(102 => 324, 106 => 325, <template_ugroup_id> => <target_ugroup_id>, …)
     */
    public function getMapping(Tracker $template_tracker, Project $target_project)
    {
        $template_ugroups = $this->permissions_retriever->getListOfInvolvedStaticUgroups($template_tracker);
        $target_ugroups   = $this->ugroup_manager->getStaticUGroups($target_project);

        $ugroups = [];
        foreach ($template_ugroups as $template_ugroup) {
            foreach ($target_ugroups as $target_ugroup) {
                if ($template_ugroup->getName() == $target_ugroup->getName()) {
                    $ugroups[$template_ugroup->getId()] = $target_ugroup->getId();
                }
            }
        }

        return $ugroups;
    }
}
