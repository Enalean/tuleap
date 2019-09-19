<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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
 * I retrieve permissions ugroup for a given tracker
 */
class Tracker_UgroupPermissionsGoldenRetriever
{

    /** @var Tracker_PermissionsDao */
    private $permissions_dao;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(
        Tracker_PermissionsDao $permissions_dao,
        UGroupManager $ugroup_manager
    ) {
        $this->permissions_dao = $permissions_dao;
        $this->ugroup_manager  = $ugroup_manager;
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getListOfInvolvedStaticUgroups(Tracker $template_tracker)
    {
        $project = $template_tracker->getProject();
        $ugroups = array();
        foreach ($this->permissions_dao->getAuthorizedStaticUgroupIds($template_tracker->getId()) as $id) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $id);
            if ($ugroup) {
                $ugroups[] = $ugroup;
            }
        }
        return $ugroups;
    }
}
