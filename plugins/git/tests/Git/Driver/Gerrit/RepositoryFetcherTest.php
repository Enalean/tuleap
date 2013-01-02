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

class RepositoryFetcherTest extends TuleapTestCase {

    private $repository_factory;
    private $git_exec;
    private $fetcher;

    public function setUp() {
        parent::setUp();
        $this->repository_factory = mock('GitRepositoryFactory');
        $this->git_exec = mock('Git_Driver_Gerrit_ExecFetch');
        $this->fetcher = partial_mock('Git_Driver_Gerrit_RepositoryFetcher', array('getGitExecForRepository'), array($this->repository_factory));
    }

    public function itDoesNothingWhenThereAreNoMatchingRepositories() {
        stub($this->repository_factory)->getRepositoriesWithRemoteServersForAllProjects()->returns(array());
        $this->fetcher->process();
    }

    public function itLoopsOverHeadsAndUpdate() {
        $repository_path = '/var/lib/tuleap/gitolite/repositories/gpig/shark.git';
        $repository = stub('GitRepository')->getFullPath()->returns($repository_path);
        stub($this->repository_factory)->getRepositoriesWithRemoteServersForAllProjects()->returns(array($repository));


        stub($this->git_exec)->lsRemoteHeads()->returns(array(
            "0682481c37d04ca3b07dafd6429f1f4ab81e23fd\trefs/heads/master",
            "881d0c6a23ffb054713ba6650271247880d04d37\trefs/heads/tuleap_integration"
        ));

        expect($this->git_exec)->updateRef()->count(2);
        expect($this->git_exec)->updateRef('master')->at(0);
        expect($this->git_exec)->updateRef('tuleap_integration')->at(1);

        stub($this->fetcher)->getGitExecForRepository($repository_path, Git_Driver_Gerrit_ProjectCreator::GERRIT_REMOTE_NAME)->returns($this->git_exec);

        $this->fetcher->process();
    }
}

?>
