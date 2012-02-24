<?php
require_once dirname(__FILE__).'/../include/GitRepository.class.php';
Mock::generate('GitRepository');
Mock::generate('User');

class testGitForkIndividualCommand extends UnitTestCase {
    
    
    /**
    * Todo : move to Forkcommmand or git.class?
     */
    function testForkShouldNotCloneAnyNonExistentRepositories() {
        $user = new MockUser();
        $repo = new MockGitRepository();
        $repo->setReturnValue('userCanRead', true);
        $command = new GitForkIndividualCommand('');
        $command->fork(array($repo, null), $user);
        
    }
}