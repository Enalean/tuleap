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
require_once dirname(__FILE__).'/../include/GitRepository.class.php';
Mock::generate('GitRepository');
Mock::generate('User');
Mock::generate('Project');
Mock::generate('Git_Backend_Gitolite');

class testGitForkIndividualCommand extends TuleapTestCase {
    
    
    /**
    * Todo : move to Forkcommmand or git.class?
     */
    function testForkShouldNotCloneAnyNonExistentRepositories() {
        $backend = new MockGit_Backend_Gitolite();
        $backend->expectOnce('fork');
        $repo = $this->GivenARepository(123, $backend);
        
        $user    = new MockUser();
        $command = new GitForkIndividualCommand('');
        $command->fork(array($repo, null), $user);
    }
    
    private function GivenARepository($id, $backend) {
        $project = new MockProject();
        $repo    = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('userCanRead', true);
        $repo->setReturnValue('getProject', $project);
        $repo->setReturnValue('getBackend', $backend);
        return $repo;
    }
    
    function testForkShouldIgnoreAlreadyExistingRepository() {
        $errorMessage = 'Repository Xxx already exists';
        $GLOBALS['Language']->setReturnValue('getText', $errorMessage);
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', $errorMessage));
        $backend = new MockGit_Backend_Gitolite();
        $backend->throwAt(1, 'fork');
        $repo1 = $this->GivenARepository(123, $backend);
        $repo2 = $this->GivenARepository(456, $backend);
        $repo3 = $this->GivenARepository(789, $backend);
        $repositories = array($repo1, $repo2, $repo3);
        
        $user    = new MockUser();
        $command = new GitForkIndividualCommand('');
        $command->fork($repositories, $user);
    }
}
?>