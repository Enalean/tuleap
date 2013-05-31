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
 * I check that given a target project and a template tracker there will be no
 * issue when creating a tracker.
 *
 * Basically this means:
 * - no message if the tracker does not have any permissions on a static ugroup
 * - a warning if the target project does not have corresponding static ugroups
 * - an info if the target project has corresponding static ugroups
 */
class Tracker_UgroupPermissionsConsistencyChecker {

    /** @var Tracker_PermissionsDao */
    private $permissions_dao;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var Tracker_UgroupPermissionsConsistencyMessenger */
    private $messenger;

    public function __construct(
        Tracker_PermissionsDao $permissions_dao,
        UGroupManager $ugroup_manager,
        Tracker_UgroupPermissionsConsistencyMessenger $messenger
    ) {
        $this->permissions_dao = $permissions_dao;
        $this->ugroup_manager  = $ugroup_manager;
        $this->messenger       = $messenger;
    }

    /**
     * @return Tracker_UgroupPermissionsConsistencyMessage
     */
    public function checkConsistency(Tracker $template_tracker, Project $target_project) {
        $ugroups = $this->getListOfInvolvedStaticUgroups($template_tracker);

        if (! $ugroups) {
            $this->messenger->allIsWell();
            return;
        }

        $template_ugroups_names = array_map(array($this, 'extractUGroupName'), $ugroups);

        $target_ugroups = $this->ugroup_manager->getStaticUGroups($target_project);
        $target_ugroups_names = array_map(array($this, 'extractUGroupName'), $target_ugroups);

        $diff = array_diff($template_ugroups_names, $target_ugroups_names);
        if ($diff) {
            $this->messenger->ugroupsMissing($diff);
        } else {
            $this->messenger->ugroupsAreTheSame($template_ugroups_names);
        }
    }

    private function extractUGroupName(UGroup $ugroup) {
        return $ugroup->getName();
    }

    private function getListOfInvolvedStaticUgroups(Tracker $template_tracker) {
        $project = $template_tracker->getProject();
        $ugroups = array();
        foreach ($this->permissions_dao->getAuthorizedStaticUgroupIds($template_tracker->getId()) as $id) {
            $ugroups[] = $this->ugroup_manager->getUGroup($project, $id);
        }
        return $ugroups;
    }
}
?>
