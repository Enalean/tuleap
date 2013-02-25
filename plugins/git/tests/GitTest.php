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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once (dirname(__FILE__).'/../include/Git.class.php');
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
class Gittest_MigrateToGerritRouteTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);
    }

    public function itDispatchesTo_migrateToGerrit_withRepoManagementView() {
        $group_id    = 101;
        $user        = stub('PFUser')->isMember($group_id, 'A')->returns(true);
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $repo        = mock('GitRepository');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns($repo);
        $git = $this->getGit($request, $usermanager, $factory, $group_id);
        
        expect($git)->addAction('migrateToGerrit', array($repo, $server_id))->at(0);
        expect($git)->addAction('redirectToRepoManagement', '*')->at(1);
        $git->request();
    }
    
    public function itIsForbiddenForNonProjectAdmins() {
        $user        = mock('PFUser');
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);
        
        $whatever_group_id = 2487374;
        $git = $this->getGit($request, $usermanager, mock('GitRepositoryFactory'), $whatever_group_id);
        
        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $whatever_group_id));
        
        $git->request();
    }
    
    public function itNeedsAValidRepoId() {
        $group_id    = 101;
        $user        = stub('PFUser')->isMember($group_id, 'A')->returns(true);
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);

        // not necessary, but we specify it to make it clear why we don't want he action to be called
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(null);
        
        $git = $this->getGit($request, $usermanager, $factory, $group_id);
        
        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');
        
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id));
        $git->request();
    }
    
    public function itNeedsAValidServerId() {
        $group_id    = 101;
        $user        = stub('PFUser')->isMember($group_id, 'A')->returns(true);
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);
        $not_valid   = 'a_string';
        $request->set('remote_server_id', $not_valid);

        // not necessary, but we specify it to make it clear why we don't want he action to be called
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(mock('GitRepositoryFactory'));
        
        $git = $this->getGit($request, $usermanager, $factory, $group_id);
        
        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');
        
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id));
        
        $git->request();
    }

    public function itForbidsGerritMigrationIfTuleapIsNotConnectedToLDAP() {
        Config::set('sys_auth_type', 'not_ldap');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->returns(mock('GitRepository'));
        $group_id    = 101;
        $user        = stub('PFUser')->isMember($group_id, 'A')->returns(true);
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGit($request, $usermanager, $factory, $group_id);
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id));
        
        $git->request();
    }
    
    public function itAllowsGerritMigrationIfTuleapIsConnectedToLDAP() {
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->returns(mock('GitRepository'));
        $group_id    = 101;
        $user        = stub('PFUser')->isMember($group_id, 'A')->returns(true);
        $usermanager = stub('UserManager')->getCurrentUser()->returns($user);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGit($request, $usermanager, $factory, $group_id);
        $git->expectAtLeastOnce('addAction');
        $git->expectNever('redirect');
        
        $git->request();
    }

    private function getGit($request, $usermanager, $factory, $group_id) {
        $git = TestHelper::getPartialMock('Git', array('_informAboutPendingEvents', 'addAction', 'addView', 'addError', 'checkSynchronizerToken', 'redirect'));
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('migrate_to_gerrit');
        $git->setGroupId($group_id);
        
        $git->setFactory($factory);        
        return $git;
    }
}

?>