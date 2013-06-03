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

class PostReceiveTest extends TuleapTestCase {
    private $git_exec_repo;
    private $extract_cross_ref;
    private $git_repository_factory;
    private $post_receive;
    private $user_manager;
    private $repository;

    public function setUp() {
        parent::setUp();
        $this->git_exec_repo          = mock('Git_Exec');
        $this->extract_cross_ref      = mock('Git_Hook_ExtractCrossReferences');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->user_manager           = mock('UserManager');
        $this->repository             = mock('GitRepository');

        $this->post_receive = new Git_Hook_PostReceive($this->git_exec_repo, $this->git_repository_factory, $this->user_manager, $this->extract_cross_ref);
    }

    public function itGetRepositoryFromFactory() {
        expect($this->git_repository_factory)->getFromFullPath('/var/lib/tuleap/gitolite/repositories/garden/dev.git')->once();
        stub($this->git_exec_repo)->revList()->returns(array());
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itGetUserFromManager() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        expect($this->user_manager)->getUserByUserName('john_doe')->once();
        stub($this->git_exec_repo)->revList()->returns(array());
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itExecutesExtractOnEachCommit() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa9'));

        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);

        $user = mock('PFUser');
        stub($this->user_manager)->getUserByUserName()->returns($user);

        expect($this->extract_cross_ref)->execute($this->repository, $user, '469eaa9', 'refs/heads/master')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itSkipsIfRepositoryIsNotKnown() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa9'));

        stub($this->git_repository_factory)->getFromFullPath()->returns(null);

        expect($this->extract_cross_ref)->execute()->never();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itFallsBackOnAnonymousIfUserIsNotKnows() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa9'));
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);

        stub($this->user_manager)->getUserByUserName()->returns(null);

        expect($this->extract_cross_ref)->execute($this->repository, new IsAnonymousUserExpectaction(), '469eaa9', 'refs/heads/master')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itIteratesOnRevs() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);

        expect($this->git_exec_repo)->revList('d8f1e57', '469eaa9')->once();
        stub($this->git_exec_repo)->revList()->returns(array());

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itIteratesOnRevsSinceStart() {
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        expect($this->git_exec_repo)->revListSinceStart('refs/heads/master', '469eaa9')->once();
        stub($this->git_exec_repo)->revListSinceStart()->returns(array());

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', '0000000000000000000000000000000000000000', '469eaa9', 'refs/heads/master');
    }


    public function itDoesntAttemptToExtractWhenBranchIsDeleted() {
        stub($this->git_exec_repo)->revListSinceStart()->returns(array('469eaa9'));
        stub($this->git_exec_repo)->revList()->returns(array('469eaa9'));
        stub($this->git_repository_factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_manager)->getUserByUserName()->returns(mock('PFUser'));

        expect($this->extract_cross_ref)->execute()->never();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', '469eaa9', '0000000000000000000000000000000000000000', 'refs/heads/master');
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
