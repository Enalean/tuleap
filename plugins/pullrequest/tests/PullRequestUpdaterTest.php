<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use \TuleapDbTestCase;
use \GitRepository;
use \ForgeConfig;

require_once 'bootstrap.php';

class PullRequestUpdaterTest extends TuleapDbTestCase
{

    /**
     * @var PullRequestUpdater
     */
    private $pull_request_updater;

    /**
     * @var Dao
     */
    private $dao;
    private $git_exec_factory;

    public function setUp()
    {
        parent::setUp();
        $this->mysqlLoadFile('plugins/pullrequest/db/install.sql');

        $sys_dbhost   = ForgeConfig::get('sys_dbhost');
        $sys_dbname   = ForgeConfig::get('sys_dbname');
        $sys_dbuser   = ForgeConfig::get('sys_dbuser');
        $sys_dbpasswd = ForgeConfig::get('sys_dbpasswd');
        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', '/tmp/');
        ForgeConfig::set('sys_dbhost', $sys_dbhost);
        ForgeConfig::set('sys_dbname', $sys_dbname);
        ForgeConfig::set('sys_dbuser', $sys_dbuser);
        ForgeConfig::set('sys_dbpasswd', $sys_dbpasswd);

        $reference_manager = mock('ReferenceManager');

        $this->dao = new Dao();
        $this->inline_comments_dao    = mock('Tuleap\PullRequest\InlineComment\Dao');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->git_exec_factory       = \Mockery::mock(GitExecFactory::class);
        $this->pull_request_updater   = new PullRequestUpdater(
            new Factory($this->dao, $reference_manager),
            mock('Tuleap\PullRequest\PullRequestMerger'),
            $this->inline_comments_dao,
            mock('Tuleap\PullRequest\InlineComment\InlineCommentUpdater'),
            new FileUniDiffBuilder(),
            mock('Tuleap\PullRequest\Timeline\TimelineEventCreator'),
            $this->git_repository_factory,
            $this->git_exec_factory,
            mock(GitPullRequestReferenceUpdater::class)
        );

        $this->git_exec = mock('\Tuleap\PullRequest\GitExec');
        $this->user     = mock('PFUser');
        stub($this->user)->getId()->returns(1337);
    }

    public function tearDown()
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function itUpdatesSourceBranchInPRs()
    {
        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'other', 'sha2', 0);
        $pr3_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'other', 'sha2', 0);

        $git_repo = mock(GitRepository::class);
        stub($git_repo)->getId()->returns(1);

        stub($this->inline_comments_dao)->searchUpToDateByPullRequestId()->returns(array());

        stub($this->git_repository_factory)->getRepositoryById()->returns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns(\Mockery::spy($this->git_exec));

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);
        $pr3 = $this->dao->searchByPullRequestId($pr3_id);

        $this->assertEqual('sha1new', $pr1['sha1_src']);
        $this->assertEqual('sha1new', $pr2['sha1_src']);
        $this->assertEqual('sha1', $pr3['sha1_src']);
    }

    public function itDoesNotUpdateSourceBranchOfOtherRepositories()
    {
        $pr1_id = $this->dao->create(2, 'title', 'description', 1, 0, 'dev', 'sha1', 2, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(2, 'title', 'description', 1, 0, 'master', 'sha1', 2, 'dev', 'sha2', 0);

        $git_repo = mock(GitRepository::class);
        stub($git_repo)->getId()->returns(1);

        stub($this->inline_comments_dao)->searchUpToDateByPullRequestId()->returns(array());

        stub($this->git_repository_factory)->getRepositoryById()->returns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns(\Mockery::spy($this->git_exec));

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        $this->assertEqual('sha1', $pr1['sha1_src']);
        $this->assertEqual('sha1', $pr2['sha1_src']);
    }

    public function itDoesNotUpdateClosedPRs()
    {
        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'dev', 'sha2', 0);

        $this->dao->markAsMerged($pr1_id);
        $this->dao->markAsAbandoned($pr2_id);

        $git_repo = mock('\GitRepository');
        stub($git_repo)->getId()->returns(1);

        stub($this->inline_comments_dao)->searchUpToDateByPullRequestId()->returns(array());

        stub($this->git_repository_factory)->getRepositoryById()->returns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns(\Mockery::spy($this->git_exec));

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        $this->assertEqual('sha1', $pr1['sha1_src']);
        $this->assertEqual('sha1', $pr2['sha1_src']);
    }
}
