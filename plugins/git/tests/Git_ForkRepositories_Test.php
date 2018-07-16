<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');


class Git_ForkRepositories_Test extends TuleapTestCase {

    public function testRenders_ForkRepositories_View() {
        $request = new Codendi_Request(array('choose_destination' => 'personal'));

        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $git->setRequest($request);
        $git->expectOnce('addView', array('forkRepositories'));

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }

    public function testExecutes_ForkRepositories_ActionWithAListOfRepos() {
        $groupId = 101;
        $repo = new GitRepository();
        $repos = array($repo);
        $user = new PFUser();
        $user->setId(42);
        $user->setUserName('Ben');
        $path = userRepoPath('Ben', 'toto');
        $forkPermissions = array();

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns($groupId);
        $project->shouldReceive('getUnixNameLowerCase')->andReturns('projectname');

        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project, array($groupId));

        $factory = new MockGitRepositoryFactory();
        $factory->setReturnValue('getRepositoryById', $repo);

        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
        $git->setProject($project);
        $git->setProjectManager($projectManager);
        $git->expectAt(0, 'addAction', array('getProjectRepositoryList', array($groupId)));
        $git->expectAt(1,'addAction', array('fork', array($repos, $project, $path, GitRepository::REPO_SCOPE_INDIVIDUAL, $user, $GLOBALS['HTML'], '/plugins/git/?group_id=101&user=42', $forkPermissions)));
        $request = new Codendi_Request(array(
            'repos' => '1001',
            'path'  => 'toto',
            'repo_access' => $forkPermissions));
        $git->setFactory($factory);
        $git->_doDispatchForkRepositories($request, $user);
    }

    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        $git = TestHelper::getPartialMock('Git', array('checkSynchronizerToken'));
        $git->throwOn('checkSynchronizerToken', new Exception());
        $this->expectException();
        $git->_doDispatchForkRepositories(null, null);

    }
}
