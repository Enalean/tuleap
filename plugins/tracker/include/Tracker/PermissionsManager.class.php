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

class Tracker_PermissionsManager {

    /** @var PermissionsManager */
    private $permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(PermissionsManager $permissions_manager, UGroupManager $ugroup_manager) {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    /**
     * Get the list of static ugroups that have a permission (either on the
     * tracker or on one of its field)
     *
     * @return UGroup[]
     */
    public function getListOfInvolvedStaticUgroups(Tracker $tracker) {
        $ugroup_ids = array();
        $this->injectUGroupIdsThatHavePermmission($ugroup_ids, $tracker, 'PLUGIN_TRACKER_ACCESS_%');
        $this->injectUGroupIdsThatHavePermmission($ugroup_ids, $tracker, 'PLUGIN_TRACKER_ADMIN');
        $this->injectUGroupIdsThatHavePermmission($ugroup_ids, $tracker, 'PLUGIN_TRACKER_FIELD_%');

        return $this->getUGroups($tracker, $ugroup_ids);
    }

    private function injectUGroupIdsThatHavePermmission(&$ugroup_ids, Tracker $tracker, $permission_type) {
        $ugroup_ids = array_unique(
            array_merge(
                $ugroup_ids,
                $this->permissions_manager->getAuthorizedUgroupIds($tracker->getId(), $permission_type)
            )
        );
    }

    private function getUGroups(Tracker $tracker, $ugroup_ids) {
        $project = $tracker->getProject();
        $ugroups = array();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroups[] = $this->ugroup_manager->getUGroup($project, $ugroup_id);
        }

        return $ugroups;
    }
}
?>
