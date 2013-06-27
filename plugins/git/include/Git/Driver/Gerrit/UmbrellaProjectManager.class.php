<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * I'm responsible of the management of
 * UmbrellaProjects on Gerrit
 *
 * -> I know how to set inheritance between
 * a project and its parents.
 */

class Git_Driver_Gerrit_UmbrellaProjectManager {

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var Git_Driver_Gerrit_MembershipManager */
    private $membership_manager;

    public function __construct(
        UGroupManager $ugroup_manager,
        ProjectManager $project_manager,
        Git_Driver_Gerrit_MembershipManager $membership_manager,
        Git_Driver_Gerrit $driver
    ) {
        $this->ugroup_manager     = $ugroup_manager;
        $this->project_manager    = $project_manager;
        $this->membership_manager = $membership_manager;
        $this->driver             = $driver;
    }

    /**
     * Creates the Umbrella Projects of a given project
     * @param Git_RemoteServer_GerritServer $gerrit_server
     * @param Project $project
     */
    public function recursivelyCreateUmbrellaProjects(Git_RemoteServer_GerritServer $gerrit_server, Project $project) {

        $ugroups        = $this->ugroup_manager->getUGroups($project);
        $admin_ugroup   = $this->getAdminUGroup($ugroups);
        $parent_project = $this->project_manager->getParentProject($project->getID());
        $project_name   = $project->getUnixName();

        if ($parent_project) {
            $this->recursivelyCreateUmbrellaProjects($gerrit_server, $parent_project);
            $this->driver->setProjectInheritance($gerrit_server, $project_name, $parent_project->getUnixName());
        }

        $this->membership_manager->createArrayOfGroupsForServer($gerrit_server, $ugroups);

        if (! $this->driver->doesTheParentProjectExist($gerrit_server, $project_name)) {
            $admin_group_name = $project_name.'/'.$admin_ugroup->getNormalizedName();
            $project_name = $this->driver->createProjectWithPermissionsOnly($gerrit_server, $project, $admin_group_name);
        }
    }

    /**
     *
     * @param UGroup[] $ugroups
     * @return null | UGroup
     */
    private function getAdminUGroup(array $ugroups) {
        foreach ($ugroups as $ugroup) {
            if ($ugroup->getId() == UGroup::PROJECT_ADMIN) {
                return $ugroup;
            }
        }

        return null;
    }

}
?>
