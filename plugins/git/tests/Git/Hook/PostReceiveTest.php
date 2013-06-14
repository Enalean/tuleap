<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

abstract class Git_Hook_PostReceive_Common extends TuleapTestCase {
    protected $log_analyzer;
    protected $extract_cross_ref;
    protected $git_repository_factory;
    protected $post_receive;
    protected $user_manager;
    protected $repository;
    protected $ci_launcher;
    protected $log_pushes;
    protected $user;

    public function setUp() {
        parent::setUp();
        $this->user                   = mock('PFUser');
        $this->log_analyzer           = mock('Git_Hook_LogAnalyzer');
        $this->extract_cross_ref      = mock('Git_Hook_ExtractCrossReferences');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->user_manager           = mock('UserManager');
        $this->repository             = mock('GitRepository');
        $this->ci_launcher            = mock('Git_Ci_Launcher');
        $this->log_pushes             = mock('Git_Hook_LogPushes');

        $this->post_receive = new Git_Hook_PostReceive(
            $this->log_analyzer,
            $this->git_repository_factory,
            $this->user_manager,
            $this->extract_cross_ref,
            $this->ci_launcher,
            $this->log_pushes
        );
    }
}

class Git_Hook_PostReceive_UserAndRepoTest extends Git_Hook_PostReceive_Common {

    public function setUp() {
        parent::setUp();

        $this->push_details           = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array());
        stub($this->log_analyzer)->getPushDetails()->returns($this->push_details);
    }

    public function itGetRepositoryFromFactory() {
        expect($this->git_repository_factory)->getFromFullPath('/var/lib/tuleap/gitolite/repositories/garden/dev.git')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itGetUserFromManager() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        expect($this->user_manager)->getUserByUserName('john_doe')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itSkipsIfRepositoryIsNotKnown() {
        stub($this->git_repository_factory)->getFromFullPath()->returns(null);

        expect($this->extract_cross_ref)->execute()->never();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itFallsBackOnAnonymousIfUserIsNotKnows() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);

        stub($this->user_manager)->getUserByUserName()->returns(null);

        expect($this->log_analyzer)->getPushDetails($this->repository, new IsAnonymousUserExpectaction(), 'd8f1e57', '469eaa9', 'refs/heads/master')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itGetsPushDetailsFromLogAnalyzer() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_manager)->getUserByUserName()->returns($this->user);

        expect($this->log_analyzer)->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }
}

class Git_Hook_PostReceive_ExtractTest extends Git_Hook_PostReceive_Common {

    public function setUp() {
        parent::setUp();
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_manager)->getUserByUserName()->returns($this->user);
    }

    public function itExecutesExtractOnEachCommit() {
        $this->push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array('469eaa9'));
        stub($this->log_analyzer)->getPushDetails()->returns($this->push_details);

        expect($this->extract_cross_ref)->execute($this->repository, $this->user, '469eaa9', 'refs/heads/master')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itDoesntAttemptToExtractWhenBranchIsDeleted() {
        $this->push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array());
        stub($this->log_analyzer)->getPushDetails()->returns($this->push_details);

        expect($this->extract_cross_ref)->execute()->never();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', '469eaa9', '0000000000000000000000000000000000000000', 'refs/heads/master');
    }
}

class Git_Hook_PostReceive_TriggerCiTest extends Git_Hook_PostReceive_Common {

    public function setUp() {
        parent::setUp();
        $this->push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array('469eaa9'));
        stub($this->log_analyzer)->getPushDetails()->returns($this->push_details);
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_manager)->getUserByUserName()->returns(mock('PFUser'));
    }

    public function itTriggersACiBuild() {
        expect($this->ci_launcher)->executeForRepository($this->repository)->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

}

class Git_Hook_PostReceive_CountPushesTest extends Git_Hook_PostReceive_Common {

    public function setUp() {
        parent::setUp();
        $this->push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array('469eaa9'));
        stub($this->log_analyzer)->getPushDetails()->returns($this->push_details);
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_manager)->getUserByUserName()->returns($this->user);
    }

    public function itLogPush() {
        expect($this->log_pushes)->executeForRepository($this->push_details)->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

}

class IsAnonymousUserExpectaction extends SimpleExpectation {
    public function test($user) {
        return ($user instanceof PFUser && $user->isAnonymous());
    }

    public function testMessage($user) {
        return "Given parameter is not an anonymous user ($user).";
    }
}

?>
