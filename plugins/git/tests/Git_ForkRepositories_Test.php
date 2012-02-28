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

class Git_ForkRepositories_Test extends TuleapTestCase {
    
    private function addInstanceVarsToGitObject(Git $git, $request, $userManager, $action = null, $permittedActions = null, $groupId = null) {
        if ($request) {
            $git->setRequest($request);
        }
        if ($userManager) {
            $git->setUserManager($userManager);
        }
        $git->setAction($action);
        $git->setPermittedActions($permittedActions);
        $git->setGroupId($groupId);
    }
    
    public function testRenders_ForkRepositories_View() {
        Mock::generatePartial('Git', 'GitSpy2', array('_doDispatchForkRepositories', 'addView'));
        $git = new GitSpy2();
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $this->addInstanceVarsToGitObject($git, $request, null);
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
        $this->addInstanceVarsToGitObject($git, null, null, null, null, $groupId);
        $git->setFactory($factory);
        $git->_doDispatchForkRepositories($request, $user);
    }
    
    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        $git = TestHelper::getPartialMock('Git', array('checkSynchronizerToken'));
        $git->throwOn('checkSynchronizerToken', new Exception());
        $this->addInstanceVarsToGitObject($git, null, null, null, null, 101);
        $this->expectException();
        $git->_doDispatchForkRepositories(null, null);

    }
}
?>
