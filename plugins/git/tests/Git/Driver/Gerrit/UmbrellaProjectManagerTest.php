<?php
/**
 * Copyright Enalean (c) 2013 - 2014. All rights reserved.
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

require_once dirname(__FILE__).'/../../../bootstrap.php';

class Git_Driver_Gerrit_ProjectCreator_CreateParentUmbrellaProjectsTest extends TuleapTestCase
{
    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    /** @var Git_Driver_Gerrit */
    protected $driver;

    /** @var Project */
    protected $project;
    protected $project_id = 103;
    protected $project_unix_name = 'mozilla';

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var Git_Driver_Gerrit_UmbrellaProjectManager */
    protected $umbrella_manager;

    protected $project_admins_gerrit_name  = 'mozilla/project_admins';

    public function setUp()
    {
        parent::setUp();

        $this->server  = mock('Git_RemoteServer_GerritServer');
        $this->project = aMockProject()->withId($this->project_id)->withUnixName($this->project_unix_name)->isPublic()->build();

        $this->project_admins_gerrit_parent_name = 'grozilla/project_admins';
        $this->parent_project = aMockProject()->withId(104)->withUnixName('grozilla')->build();

        $this->parent_project_admins = aMockUGroup()->withId(ProjectUGroup::PROJECT_ADMIN)->withNormalizedName('project_admins')->build();

        $this->project_admins = aMockUGroup()->withId(ProjectUGroup::PROJECT_ADMIN)->withNormalizedName('project_admins')->build();

        $this->driver = mock('Git_Driver_Gerrit');
        stub($this->driver)->doesTheParentProjectExist()->returns(false);

        $driver_factory = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);

        $this->ugroup_manager = mock('UGroupManager');
        stub($this->ugroup_manager)->getUGroups($this->project)->returns(array($this->project_admins));
        stub($this->ugroup_manager)->getUGroups($this->parent_project)->returns(array($this->parent_project_admins));

        $this->membership_manager = mock('Git_Driver_Gerrit_MembershipManager');
        stub($this->membership_manager)->createArrayOfGroupsForServer()->returns(array($this->project_admins, $this->parent_project_admins));

        $this->project_manager = mock('ProjectManager');

        $this->umbrella_manager = new Git_Driver_Gerrit_UmbrellaProjectManager(
            $this->ugroup_manager,
            $this->project_manager,
            $this->membership_manager,
            $driver_factory
        );
    }

    public function itOnlyCallsCreateParentProjectOnceIfTheProjectHasNoParents()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns(null);

        expect($this->driver)->createProjectWithPermissionsOnly($this->server, $this->project, $this->project_admins_gerrit_name)->once();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function itOnlyCallsCreateParentProjectTwiceIfTheProjectHasOneParent()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns($this->parent_project);
        stub($this->project_manager)->getParentProject($this->parent_project->getID())->returns(null);
        expect($this->driver)->createProjectWithPermissionsOnly()->count(2);

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function itCallsCreateParentProjectWithTheCorrectParameters()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns($this->parent_project);
        stub($this->project_manager)->getParentProject($this->parent_project->getID())->returns(null);

        expect($this->driver)->createProjectWithPermissionsOnly($this->server, $this->project, $this->project_admins_gerrit_name)->at(0);
        expect($this->driver)->createProjectWithPermissionsOnly($this->server, $this->parent_project, $this->project_admins_gerrit_parent_name)->at(1);

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function itMigratesTheUserGroupsAlsoForParentUmbrellaProjects()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns($this->parent_project);
        stub($this->project_manager)->getParentProject($this->parent_project->getID())->returns(null);

        expect($this->membership_manager)->createArrayOfGroupsForServer()->count(2);

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function itCallsTheDriverToSetTheParentProjectIfAny()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns($this->parent_project);
        stub($this->project_manager)->getParentProject($this->parent_project->getID())->returns(null);

        expect($this->driver)->setProjectInheritance($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName())->once();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function itDoesntCallTheDriverToSetTheParentProjectIfNone()
    {
        stub($this->project_manager)->getParentProject($this->project->getID())->returns(null);

        expect($this->driver)->setProjectInheritance($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName())->never();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }
}
