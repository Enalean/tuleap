<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once dirname(__FILE__).'/../include/Git.class.php';

Mock::generate('User');
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');

class Git_ForkCrossProject_Test extends TuleapTestCase {

    public function testExecutes_ForkCrossProject_ActionWithForkRepositoriesView() {
        $groupId = 101;
        $forkPermissions = array();
        $toProjectId = 100;
        $toProject = new MockProject();
        $toProject->setReturnValue('getId', $toProjectId);
        
        $repo  = new GitRepository();
        $repos = array($repo);
        $repo_ids = '200';
        
        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        
        $usermanager = new MockUserManager();
        $usermanager->setReturnValue('getCurrentUser', $user);
        
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $toProject, array($toProjectId));
        
        $repositoryFactory = new MockGitRepositoryFactory();
        $repositoryFactory->setReturnValue('getRepositoryById', $repo, array($repo_ids));

        $request = new Codendi_Request(array(
                                        'choose_destination' => 'project',
                                        'to_project' => $toProjectId,
                                        'repos' => $repo_ids,
                                        'repo_access' => $forkPermissions));

        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
        $git->setGroupId($groupId);
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setProjectManager($projectManager);
        $git->setFactory($repositoryFactory);

        $git->expectCallCount('addAction', 2);
        $git->expectAt(0, 'addAction', array('fork', array($repos, $toProject, '', GitRepository::REPO_SCOPE_PROJECT, $user, $GLOBALS['HTML'], '/plugins/git/?group_id=100', $forkPermissions)));
        $git->expectAt(1, 'addAction', array('getProjectRepositoryList', array($groupId)));
        $git->expectOnce('addView', array('forkRepositories'));
        
        $git->_dispatchActionAndView('do_fork_repositories', null, null, $user);
    }
    
    public function testAddsErrorWhenRepositoriesAreMissing() {
        $group_id = 11;
        
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter_repos', '*'));
        
        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addError', 'redirect', 'checkSynchronizerToken'));
        $git->setGroupId($group_id);
        $git->setFactory(new MockGitRepositoryFactory());
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array('to_project' => 234, 'repo_access' => array()));

        $git->_doDispatchForkCrossProject($request, null);
    }
    
    public function testAddsErrorWhenDestinationProjectIsMissing() {
        $group_id = 11;
        
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter_to_project', '*'));
        
        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addError', 'redirect', 'checkSynchronizerToken'));
        $git->setGroupId($group_id);
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array(
            'repos'       => array('qdfj'),
            'repo_access' => array()
        ));

        $git->_doDispatchForkCrossProject($request, null);
    }
    
    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        $git = TestHelper::getPartialMock('Git', array('checkSynchronizerToken'));
        $git->throwOn('checkSynchronizerToken', new Exception());
        $this->expectException();
        $git->_doDispatchForkCrossProject(null, null);
    }
    
    function testUserMustBeAdminOfTheDestinationProject() {
        $adminMsg = 'must_be_admin_to_create_project_repo';
        $GLOBALS['Language']->setReturnValue('getText', $adminMsg, array('plugin_git', $adminMsg, '*'));
        
        $user = mock('PFUser');
        $user->setReturnValue('isMember', false, array(666, 'A'));
        
        $request = new Codendi_Request(array(
            'to_project'  => 666,
            'repos'       => array(1),
            'repo_access' => array()
        ));
        
        $git = TestHelper::getPartialMock('Git', array('checkSynchronizerToken', 'addError', 'addAction', 'getText'));
        $git->setGroupId(123);
        $git->expectOnce('addError', array($git->getText($adminMsg)));
        $git->expectNever('addAction');
        
        $git->_doDispatchForkCrossProject($request, $user);
    }
}
?>
