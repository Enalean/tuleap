<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

require_once 'bootstrap.php';

abstract class GitPermissionsManagerTest extends TuleapTestCase {
    protected $permissions_manager;
    protected $git_permissions_manager;
    protected $git_permissions_dao;
    protected $git_system_event_manager;

    public function setUp() {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        PermissionsManager::setInstance($this->permissions_manager);
        $this->git_permissions_dao      = mock('Git_PermissionsDao');
        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->git_permissions_manager  = new GitPermissionsManager(
            $this->git_permissions_dao,
            $this->git_system_event_manager,
            mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
            mock('Tuleap\Git\Permissions\DefaultFineGrainedPermissionSaver'),
            mock('Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\FineGrainedDao'),
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock('ProjectHistoryDao')
        );
    }

    public function tearDown() {
        parent::tearDown();
        PermissionsManager::clearInstance();
    }
}

class GitPermissionsManager_SiteAccessUpdateTest extends GitPermissionsManagerTest {

    public function testWhenSwitchingFromAnonymousToRegularItUpdatesAllProjectsThatWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsDar(array('group_id' => 101), array('group_id' => 104));

        expect($this->git_permissions_dao)->updateAllAnonymousAccessToRegistered()->once();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(101, 104))->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromAnonymousToRegularItDoesNothingWhenNoProjectsWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsEmptyDar();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousAccessToRegistered()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToAnonymousItDoesNothing() {
        expect($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->never();
        expect($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousAccessToRegistered()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedAccessToRegistered()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromAnonymousToRestrictedItUpdatesAllProjectsThatWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsDar(array('group_id' => 101), array('group_id' => 104));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(101, 104))->once();
        expect($this->git_permissions_dao)->updateAllAnonymousAccessToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromRestrictedToAnonymousItUpdatesAllProjectThatWereUsingUnRestricted() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsDar(array('group_id' => 102), array('group_id' => 107));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102, 107))->once();
        expect($this->git_permissions_dao)->updateAllAuthenticatedAccessToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromRestrictedToRegularItUpdatesAllProjectThatWereUsingUnRestricted() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsDar(array('group_id' => 102), array('group_id' => 107));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102, 107))->once();
        expect($this->git_permissions_dao)->updateAllAuthenticatedAccessToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRestrictedToRegularItDoesNothingWhenNoProjectsWereUsingAuthenticated() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsEmptyDar();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedAccessToRegistered()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToRestrictedItDoesNothing() {
        expect($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->never();
        expect($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousAccessToRegistered()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedAccessToRegistered()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::RESTRICTED);
    }
}

class GitPermissionsManager_ProjectAccessUpdateTest extends GitPermissionsManagerTest {

    private $project;

    public function setUp() {
        parent::setUp();
        $this->project = aMockProject()->withId(102)->build();
    }

    public function testWhenSwitchingFromPublicToPrivateItSetsProjectMembersForAllPublicRepositories() {
        expect($this->git_permissions_dao)->disableAnonymousRegisteredAuthenticated(102)->once();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102))->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE);
    }

    public function testWhenSwitchingFromPublicToUnrestrictedItDoesNothing() {
        expect($this->git_permissions_dao)->disableAnonymousRegisteredAuthenticated()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC, Project::ACCESS_PUBLIC_UNRESTRICTED);
    }

    public function testWhenSwitchingFromPrivateToPublicItDoesNothing() {
        expect($this->git_permissions_dao)->disableAnonymousRegisteredAuthenticated()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromPrivateToUnrestrictedItDoesNothing() {
        expect($this->git_permissions_dao)->disableAnonymousRegisteredAuthenticated()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPublicItRemoveAccessToAuthenticated() {
        expect($this->git_permissions_dao)->disableAuthenticated(102)->once();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102))->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC);
    }

    public function testWhenSwitchingFromUnrestrictedToPrivateItSetsProjectMembersForAllPublicRepositories() {
        expect($this->git_permissions_dao)->disableAnonymousRegisteredAuthenticated(102)->once();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102))->once();

        $this->git_permissions_manager->updateProjectAccess($this->project, Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE);
    }
}