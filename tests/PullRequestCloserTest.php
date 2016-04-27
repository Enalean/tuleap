<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use TuleapTestCase;
use GitRepository;
use ForgeConfig;

require_once 'bootstrap.php';

class PullRequestCloserTest extends TuleapTestCase {

    private $git_repository_dir;

    /**
     * @var PullRequestCloser
     */
    private $pull_request_closer;

    /**
     * @var GitRepository
     */
    private $git_repository;

    /**
     * @var GitExec
     */
    private $git_exec;

    /**
     * @var Dao
     */
    private $dao;

    public function setUp() {
        parent::setUp();

        $this->git_repository_dir = '/tmp/tuleap-pullrequest-git-exec-test_'.rand(0, 99999999);
        mkdir($this->git_repository_dir);
        system("cd $this->git_repository_dir && git init 2>&1 >/dev/null");
        system("cd $this->git_repository_dir && git checkout -b master --quiet");
        file_put_contents("$this->git_repository_dir/toto", "stuff");
        system("cd $this->git_repository_dir && git add . && git commit --quiet -m 'Add toto'");

        system("cd $this->git_repository_dir && git checkout -b dev --quiet");
        file_put_contents("$this->git_repository_dir/preguilt", "semibarbarous");
        system("cd $this->git_repository_dir && git add . && git commit --quiet -m 'Add preguilt'");

        $this->dao                 = mock('Tuleap\PullRequest\Dao');
        $this->pull_request_closer = new PullRequestCloser($this->dao);
        $this->git_repository      = stub('GitRepository')->getFullPath()->returns($this->git_repository_dir);
        $this->git_exec            = new GitExec($this->git_repository_dir);

        stub($this->git_repository)->getId()->returns(73);

        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', '/tmp/');
    }

    public function tearDown() {
        system("rm -rf $this->git_repository_dir");
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function itMergesABranchIntoAnEmptyBranch() {
        stub($this->dao)->markAsMerged(1)->returns(true);

        $chat_ouane_master = $this->git_exec->getBranchSha1('master');
        $chat_ouane_dev    = $this->git_exec->getBranchSha1('dev');

        $pull_request = new PullRequest(
            1,
            'title',
            'description',
            73,
            105,
            1456309611,
            'dev',
            $chat_ouane_dev,
            'master',
            $chat_ouane_master,
            'R'
        );

        $result = $this->pull_request_closer->fastForwardMerge(
            $this->git_repository,
            $pull_request
        );

        $this->assertTrue($result);

        system("cd $this->git_repository_dir && git checkout master --quiet");

        $this->assertTrue(is_file("$this->git_repository_dir/preguilt"));
        $this->assertEqual(file_get_contents("$this->git_repository_dir/preguilt"), "semibarbarous");
    }

    public function itMergesABranchIntoAnotherBranchThatIsNotMaster() {
        stub($this->dao)->markAsMerged(1)->returns(true);

        file_put_contents("$this->git_repository_dir/antiracing", "hatlike");
        system("cd $this->git_repository_dir && git checkout -b feature --quiet && git add . && git commit --quiet -m 'Add antiracing'");

        $chat_ouane_dev     = $this->git_exec->getBranchSha1('dev');
        $chat_ouane_feature = $this->git_exec->getBranchSha1('feature');

        $pull_request = new PullRequest(
            1,
            'title',
            'description',
            73,
            105,
            1456309611,
            'feature',
            $chat_ouane_feature,
            'dev',
            $chat_ouane_dev,
            'R'
        );

        $result = $this->pull_request_closer->fastForwardMerge(
            $this->git_repository,
            $pull_request
        );

        $this->assertTrue($result);

        system("cd $this->git_repository_dir && git checkout dev --quiet");

        $this->assertTrue(is_file("$this->git_repository_dir/antiracing"));
        $this->assertEqual(file_get_contents("$this->git_repository_dir/antiracing"), "hatlike");
    }

    public function itReturnsTrueIfPullRequestIsAlreadyMerged() {
        $chat_ouane_master = $this->git_exec->getBranchSha1('master');
        $chat_ouane_dev    = $this->git_exec->getBranchSha1('dev');

        $pull_request = new PullRequest(
            1,
            'title',
            'description',
            73,
            105,
            1456309611,
            'dev',
            $chat_ouane_dev,
            'master',
            $chat_ouane_master,
            'M'
        );

        expect($this->dao)->markAsMerged()->never();

        $result = $this->pull_request_closer->fastForwardMerge(
            $this->git_repository,
            $pull_request
        );

        $this->assertTrue($result);
    }

    public function itThrowsAnExceptionIfPullRequestWasPreviouslyAbandoned() {
        $chat_ouane_master = $this->git_exec->getBranchSha1('master');
        $chat_ouane_dev    = $this->git_exec->getBranchSha1('dev');

        $pull_request = new PullRequest(
            1,
            'title',
            'description',
            73,
            105,
            1456309611,
            'dev',
            $chat_ouane_dev,
            'master',
            $chat_ouane_master,
            'A'
        );

        expect($this->dao)->markAsMerged()->never();
        $this->expectException('Tuleap\PullRequest\Exception\PullRequestCannotBeMerged');

        $this->pull_request_closer->fastForwardMerge(
            $this->git_repository,
            $pull_request
        );
    }
}
