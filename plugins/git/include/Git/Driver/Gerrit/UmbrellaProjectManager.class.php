<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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

class Git_Driver_Gerrit_UmbrellaProjectManager
{

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $driver_factory;

    /** @var Git_Driver_Gerrit_MembershipManager */
    private $membership_manager;

    public function __construct(
        UGroupManager $ugroup_manager,
        ProjectManager $project_manager,
        Git_Driver_Gerrit_MembershipManager $membership_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory
    ) {
        $this->ugroup_manager     = $ugroup_manager;
        $this->project_manager    = $project_manager;
        $this->membership_manager = $membership_manager;
        $this->driver_factory     = $driver_factory;
    }

    /**
     * Creates the Umbrella Projects of a given project
     * @param Git_RemoteServer_GerritServer[] $gerrit_servers
     */
    public function recursivelyCreateUmbrellaProjects(array $gerrit_servers, Project $project)
    {
        $parent_project = $this->project_manager->getParentProject($project->getID());

        $this->createProjectOnServers($gerrit_servers, $project);

        if (!$parent_project) {
            $this->resetProjectInheritanceOnServers($gerrit_servers, $project);
            return;
        }

        $this->recursivelyCreateUmbrellaProjects($gerrit_servers, $parent_project);
        $this->setProjectInheritanceOnServers($gerrit_servers, $project, $parent_project);
    }

    /**
     * set the inheritance on all Project's servers
     * @param array $gerrit_servers
     * @param type $project_name
     * @param type $parent_project_name
     */
    private function setProjectInheritanceOnServers(array $gerrit_servers, Project $project, Project $parent_project)
    {
        foreach ($gerrit_servers as $gerrit_server) {
            $this->driver_factory
                ->getDriver($gerrit_server)
                ->setProjectInheritance(
                    $gerrit_server,
                    $project->getUnixName(),
                    $parent_project->getUnixName()
                );
        }
    }

    /**
     * @param array $gerrit_servers
     */
    private function resetProjectInheritanceOnServers(array $gerrit_servers, Project $project)
    {
        foreach ($gerrit_servers as $gerrit_server) {
            $this->driver_factory
                ->getDriver($gerrit_server)
                ->resetProjectInheritance(
                    $gerrit_server,
                    $project->getUnixName()
                );
        }
    }

    /**
     * @param array $gerrit_servers
     */
    private function createProjectOnServers(array $gerrit_servers, Project $project)
    {
        $ugroups      = $this->ugroup_manager->getUGroups($project);
        $admin_ugroup = $this->getAdminUGroup($ugroups);
        $project_name = $project->getUnixName();

        foreach ($gerrit_servers as $gerrit_server) {
            $this->membership_manager->createArrayOfGroupsForServer($gerrit_server, $ugroups);
            $driver = $this->driver_factory->getDriver($gerrit_server);

            if (! $driver->doesTheParentProjectExist($gerrit_server, $project_name)) {
                $admin_group_name = $project_name . '/' . $admin_ugroup->getNormalizedName();
                $project_name = $driver->createProjectWithPermissionsOnly($gerrit_server, $project, $admin_group_name);
            }
        }
    }

    /**
     *
     * @param ProjectUGroup[] $ugroups
     * @return null | ProjectUGroup
     */
    private function getAdminUGroup(array $ugroups)
    {
        foreach ($ugroups as $ugroup) {
            if ($ugroup->getId() == ProjectUGroup::PROJECT_ADMIN) {
                return $ugroup;
            }
        }

        return null;
    }
}
