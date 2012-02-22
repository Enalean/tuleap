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
Mock::generatePartial('Git', 'GitSpy', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView'));
Mock::generatePartial('Git', 'GitSpyForErrors', array('definePermittedActions', '_informAboutPendingEvents', 'addError', 'redirect'));
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
}
//TODO 
// - verify that the user is admin of the project
// - clean gitaction tests of duplication
// - Ask the user to which project he wants to fork by listing all projects of which he is admin
// - move fork methods from gitrepository to gitforkcommands
//
// - add requirement about the repos and git command passed to the action
// - pass on a real project instead of a project id

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
        //forkCommand, $groupId, array $repos, User $user, Layout $response
        $groupId = 101;
        $git->expectOnce('addAction', array('forkCrossProject', array($groupId, $repos, $toProject, $user, $GLOBALS['HTML'])));
        $git->expectOnce('addView', array('forkRepositories'));

        $request = new Codendi_Request(array(
                                        'to_project' => $toProjectId,
                                        'repos' => $repo_ids));

        $git->_addInstanceVars($request, $usermanager, null, null, $groupId);
        $git->setProjectManager($projectManager);
        $repositoryFactory = new MockGitRepositoryFactory();
        $repositoryFactory->setReturnValue('getRepository', $repo, array($groupId, $repo_ids[0]));
        $git->setFactory($repositoryFactory);

        $git->_dispatchActionAndView('fork_cross_project', null, null, $user);
    }
    
    public function testAddsErrorWhenRepositoriesAreMissing() {
        $git = new GitSpyForErrors();
        $group_id = 11;
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter', array('repos')));
        
        $git->_addInstanceVars(null, null, null, null, $group_id);
        $git->setFactory(new MockGitRepositoryFactory());
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git?group_id='.$group_id));

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
        $git->expectOnce('redirect', array('/plugins/git?group_id='.$group_id));

        $request = new Codendi_Request(array(
                                        'repos' => array('qdfj')));

        $git->_doDispatchForkCrossProject($request, null);
    }
}


?>