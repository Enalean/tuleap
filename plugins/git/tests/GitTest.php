<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');

class GitTest extends TuleapTestCase  {

    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView() {
        $usermanager = new MockUserManager();
        $request     = new HTTPRequest();

        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('del');
        $git->setPermittedActions(array('del'));
        $git->setGroupId(101);

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $git->expectOnce('addAction', array('deleteRepository', '*'));
        $git->expectOnce('addView', array('index'));

        $git->request();
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonal() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectOnce('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }

    public function testDispatchToForkRepositoriesIfRequestsPersonalAndNonMember() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectNever('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', false);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }

    public function testDispatchToForkCrossProjectIfRequestsProject() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkCrossProject', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'project'));
        $git->setRequest($request);

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->expectOnce('_doDispatchForkCrossProject');
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }
}

abstract class Git_RouteBaseTestCase extends TuleapTestCase {

    protected $repo_id  = 999;
    protected $group_id = 101;

    public function setUp() {
        parent::setUp();
        $this->user         = mock('PFUser');
        $this->admin        = stub('PFUser')->isMember($this->group_id, 'A')->returns(true);
        $this->user_manager = mock('UserManager');
    }

    protected function getGit($request, $factory) {
        $git = TestHelper::getPartialMock('Git', array('_informAboutPendingEvents', 'addAction', 'addView', 'addError', 'checkSynchronizerToken', 'redirect'));
        $git->setRequest($request);
        $git->setUserManager($this->user_manager);
        $git->setGroupId($this->group_id);
        $git->setFactory($factory);

        return $git;
    }

    protected function assertItIsForbiddenForNonProjectAdmins() {
        stub($this->user_manager)->getCurrentUser()->returns($this->user);
        $request = aRequest()->with('repo_id', $this->repo_id)->build();

        $git = $this->getGit($request, mock('GitRepositoryFactory'));

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $this->group_id));

        $git->request();
    }

    protected function assertItNeedsAValidRepoId() {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);

        // not necessary, but we specify it to make it clear why we don't want he action to be called
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(null);

        $git = $this->getGit($request, $factory);

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');

        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $this->group_id));
        $git->request();
    }
}

class Git_DisconnectFromGerritRouteTest extends Git_RouteBaseTestCase {

    public function itDispatchTo_disconnectFromGerrit_withRepoManagementView() {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request = aRequest()->with('repo_id', $this->repo_id)->build();
        $repo    = mock('GitRepository');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns($repo);
        $git = $this->getGit($request, $factory);

        expect($git)->addAction('disconnectFromGerrit', array($repo))->at(0);
        expect($git)->addAction('redirectToRepoManagement', '*')->at(1);
        $git->request();
    }

    public function itIsForbiddenForNonProjectAdmins() {
        $this->assertItIsForbiddenForNonProjectAdmins();
    }

    public function itNeedsAValidRepoId() {
        $this->assertItNeedsAValidRepoId();
    }

    protected function getGit($request, $factory) {
        $git = parent::getGit($request, $factory);
        $git->setAction('disconnect_gerrit');
        return $git;
    }
}

class Gittest_MigrateToGerritRouteTest extends Git_RouteBaseTestCase {

    public function setUp() {
        parent::setUp();
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);
    }

    public function itDispatchesTo_migrateToGerrit_withRepoManagementView() {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $migrate_access_right = true;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('migrate_access_right', $migrate_access_right);
        $repo        = mock('GitRepository');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns($repo);
        $git = $this->getGit($request, $factory);

        expect($git)->addAction('migrateToGerrit', array($repo, $server_id, $migrate_access_right))->at(0);
        expect($git)->addAction('redirectToRepoManagement', '*')->at(1);
        $git->request();
    }

    public function itIsForbiddenForNonProjectAdmins() {
        $this->assertItIsForbiddenForNonProjectAdmins();
    }

    public function itNeedsAValidRepoId() {
        $this->assertItNeedsAValidRepoId();
    }

    public function itNeedsAValidServerId() {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);
        $not_valid   = 'a_string';
        $request->set('remote_server_id', $not_valid);

        // not necessary, but we specify it to make it clear why we don't want he action to be called
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(mock('GitRepositoryFactory'));

        $git = $this->getGit($request, $factory);

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');

        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $this->group_id));

        $git->request();
    }

    public function itForbidsGerritMigrationIfTuleapIsNotConnectedToLDAP() {
        Config::set('sys_auth_type', 'not_ldap');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->returns(mock('GitRepository'));
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGit($request, $factory);
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $this->group_id));

        $git->request();
    }

    public function itAllowsGerritMigrationIfTuleapIsConnectedToLDAP() {
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->returns(mock('GitRepository'));
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGit($request, $factory);
        $git->expectAtLeastOnce('addAction');
        $git->expectNever('redirect');

        $git->request();
    }

    protected function getGit($request, $factory) {
        $git = parent::getGit($request, $factory);
        $git->setAction('migrate_to_gerrit');
        return $git;
    }
}

?>