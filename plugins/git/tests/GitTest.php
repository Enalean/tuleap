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
require_once (dirname(__FILE__).'/../include/Git.class.php');
require_once(dirname(__FILE__).'/../../../src/common/valid/ValidFactory.class.php');
require_once(dirname(__FILE__).'/../../../src/common/user/UserManager.class.php');
Mock::generatePartial('Git', 'GitSpy', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
Mock::generatePartial('Git', 'GitSpyForErrors', array('definePermittedActions', '_informAboutPendingEvents', 'addError', 'redirect', 'checkSynchronizerToken'));
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');
class GitTest extends TuleapTestCase {
    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView() {
        $git = new GitSpy();
        $git->expectOnce('addAction', array('deleteRepository', '*'));
        $git->expectOnce('addView', array('index'));
        
        $usermanager = new MockUserManager();
        $unimportantGroupId = 101;
        $request = new HTTPRequest();

        $git->_addInstanceVars($request, $usermanager, 'del', array('del'), $unimportantGroupId);
        $git->request();
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonal() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->_addInstanceVars($request, null);
        $git->expectOnce('_doDispatchForkRepositories');
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }

    public function testDispatchToForkCrossProjectIfRequestsProject() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkCrossProject', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'project'));
        $git->_addInstanceVars($request, null);
        $git->expectOnce('_doDispatchForkCrossProject');
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }
}

class Git_ForkRepositories_Test extends TuleapTestCase {
    public function testRenders_ForkRepositories_View() {
        Mock::generatePartial('Git', 'GitSpy2', array('_doDispatchForkRepositories', 'addView'));
        $git = new GitSpy2();
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->_addInstanceVars($request, null);
        $git->expectOnce('addView', array('forkRepositories'));
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null);

    }
    
    public function testExecutes_ForkRepositories_ActionWithAListOfRepos() {
        $groupId = 101;
        $repo = new GitRepository();
        $repos = array($repo);
        $user = new User();
        $user->setUserName('Ben');
        $path = userRepoPath('Ben', 'toto');
        
        $factory = new MockGitRepositoryFactory();
        $factory->setReturnValue('getRepository', $repo);
        
        $git = new GitSpy();
        $git->expectAt(0, 'addAction', array('getProjectRepositoryList', array($groupId)));
        $git->expectAt(1,'addAction', array('forkIndividualRepositories', array($groupId, $repos, $path, $user, $GLOBALS['HTML'])));
        $request = new Codendi_Request(array(
            'repos' => array('1001'),
            'path'  => 'toto'));
        $git->_addInstanceVars(null, null, null, null, $groupId);
        $git->setFactory($factory);
        $git->_doDispatchForkRepositories($request, $user);
    }
    
    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        Mock::generatePartial('Git', 'GitSpyToken', array('checkSynchronizerToken'));
        $git = new GitSpyToken();
        $git->throwOn('checkSynchronizerToken', new Exception());
        $git->_addInstanceVars(null, null, null, null, 101);
        $this->expectException();
        $git->_doDispatchForkRepositories(null, null);

    }
    
}
class Git_ForkCrossProject_Test extends TuleapTestCase {
    public function testExecutes_ForkCrossProject_ActionWithForkRepositoriesView() {
        $user = new User();
        $toProjectId = 100;
        $toProject = new MockProject();
        $toProject->setReturnValue('getId', $toProjectId);
        $repo = new GitRepository();
        $repos = array($repo);
        $repo_ids = array(200);
        $usermanager = new MockUserManager();
        $usermanager->setReturnValue('getCurrentUser', $user);

        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $toProject, array($toProjectId));
        $git = new GitSpy();
        $groupId = 101;
        $git->expectOnce('addAction', array('forkCrossProjectRepositories', array($groupId, $repos, $toProject, $user, $GLOBALS['HTML'])));
        $git->expectOnce('addView', array('forkRepositories'));

        $request = new Codendi_Request(array(
                                        'choose_destination' => 'project',
                                        'to_project' => $toProjectId,
                                        'repos' => $repo_ids));

        $git->_addInstanceVars($request, $usermanager, null, null, $groupId);
        $git->setProjectManager($projectManager);
        $repositoryFactory = new MockGitRepositoryFactory();
        $repositoryFactory->setReturnValue('getRepository', $repo, array($groupId, $repo_ids[0]));
        $git->setFactory($repositoryFactory);

        $git->_dispatchActionAndView('do_fork_repositories', null, null, $user);
    }
    
    public function testAddsErrorWhenRepositoriesAreMissing() {
        $git = new GitSpyForErrors();
        $group_id = 11;
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter', array('repos')));
        
        $git->_addInstanceVars(null, null, null, null, $group_id);
        $git->setFactory(new MockGitRepositoryFactory());
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array(
                                        'to_project' => 234));

        $git->_doDispatchForkCrossProject($request, null);
    }
    public function testAddsErrorWhenDestinationProjectIsMissing() {
        $git = new GitSpyForErrors();
        $group_id = 11;
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter', array('to_project')));

        $git->_addInstanceVars(null, null, null, null, $group_id);
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array(
                                        'repos' => array('qdfj')));

        $git->_doDispatchForkCrossProject($request, null);
    }
    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        $git = new GitSpyToken();
        $git->throwOn('checkSynchronizerToken', new Exception());
        $git->_addInstanceVars(null, null, null, null, 101);
        $this->expectException();
        $git->_doDispatchForkCrossProject(null, null);

    }

}

?>