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

require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RepositoryFetcher.class.php';

class Git_Driver_Gerrit_RepositoryFetcher_BaseTest extends TuleapTestCase {

    protected $repository_factory;
    protected $git_exec;
    protected $fetcher;

    public function setUp() {
        parent::setUp();
        $this->repository_factory = mock('GitRepositoryFactory');
        $this->git_exec = mock('Git_Driver_Gerrit_ExecFetch');
        $this->logger  = mock('BackendLogger');
        $this->fetcher = partial_mock('Git_Driver_Gerrit_RepositoryFetcher', array('getGitExecForRepository'), array($this->repository_factory, $this->logger));
    }
}

class Git_Driver_Gerrit_RepositoryFetcher_WithoutDataTest extends Git_Driver_Gerrit_RepositoryFetcher_BaseTest {
    public function itDoesNothingWhenThereAreNoMatchingRepositories() {
        stub($this->repository_factory)->getActiveRepositoriesWithRemoteServersForAllProjects()->returns(array());
        $this->fetcher->process();
    }
}

class Git_Driver_Gerrit_RepositoryFetcher_WithOneRepoTest extends Git_Driver_Gerrit_RepositoryFetcher_BaseTest {
    public function setUp() {
        parent::setUp();

        $this->repository_id   = 124;
        $this->repository_path = '/var/lib/tuleap/gitolite/repositories/gpig/shark.git';
        $repository = mock('GitRepository');
        stub($repository)->getFullPath()->returns($this->repository_path);
        stub($repository)->getId()->returns($this->repository_id);
        stub($this->repository_factory)->getActiveRepositoriesWithRemoteServersForAllProjects()->returns(array($repository));


        stub($this->git_exec)->lsRemoteHeads()->returns(array(
            "0682481c37d04ca3b07dafd6429f1f4ab81e23fd\trefs/heads/master",
            "881d0c6a23ffb054713ba6650271247880d04d37\trefs/heads/tuleap_integration"
        ));

        stub($this->fetcher)->getGitExecForRepository($this->repository_path, Git_Driver_Gerrit_ProjectCreator::GERRIT_REMOTE_NAME)->returns($this->git_exec);
    }

    public function itLoopsOverHeadsAndUpdate() {
        expect($this->git_exec)->updateRef()->count(2);
        expect($this->git_exec)->updateRef('master')->at(0);
        expect($this->git_exec)->updateRef('tuleap_integration')->at(1);

        $this->fetcher->process();
    }

    public function itLogsTheRepositoryItAttemptsToProcess() {
        expect($this->logger)->info()->count(3);
        expect($this->logger)->info("Gerrit fetch all repositories...")->at(0);
        expect($this->logger)->info("Gerrit fetch $this->repository_path (id: $this->repository_id)")->at(1);
        expect($this->logger)->info("Gerrit fetch all repositories done")->at(2);
        $this->fetcher->process();
    }

    public function itLogsTheGitExceptionsWithMeaningFullMessage() {
        $exception = new Git_Command_Exception('git fetch gerrit -q', array('Some nasty', 'error'), 244);
        stub($this->git_exec)->updateRef()->throws($exception);

        expect($this->logger)->error("Error raised while updating repository $this->repository_path (id: $this->repository_id)", $exception)->once();

        $this->fetcher->process();
    }

    public function itLogsOtherExceptionWithADummyMessage() {
        $exception = new Exception('Zorglub');
        stub($this->git_exec)->updateRef()->throws($exception);

        expect($this->logger)->error("Unknown error", $exception)->once();

        $this->fetcher->process();
    }
}

?>
