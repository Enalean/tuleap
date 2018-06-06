<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        \ForgeConfig::store();
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testPullRequestReferenceIsCreatedDirectlyWhenReferenceIsAvailable()
    {
        \ForgeConfig::set('enable_creation_git_ref_at_pr_creation', 1);

        $dao               = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao);

        $pull_request           = \Mockery::mock(PullRequest::class);
        $executor_source        = \Mockery::mock(GitExec::class);
        $executor_destination   = \Mockery::mock(GitExec::class);
        $repository_destination = \Mockery::mock(\GitRepository::class);

        $dao->shouldReceive('createGitReferenceForPullRequest')->andReturns(1)->once();
        $executor_destination->shouldReceive('getReferencesFromPattern')->andReturns([])->once();
        $executor_source->shouldReceive('push')->once();

        $pull_request->shouldReceive('getId')->andReturns(1);
        $pull_request->shouldReceive('getSha1Src')->andReturns('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $repository_destination->shouldReceive('getPath')->andReturns('/path');

        $reference_creator->createPullRequestReference($pull_request, $executor_source, $executor_destination, $repository_destination);
    }

    public function testPullRequestReferenceIsCreatedWithFirstAvailabelOneWhenTheInitialOneIsAlreadyTaken()
    {
        \ForgeConfig::set('enable_creation_git_ref_at_pr_creation', 1);

        $dao               = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao);

        $pull_request           = \Mockery::mock(PullRequest::class);
        $executor_source        = \Mockery::mock(GitExec::class);
        $executor_destination   = \Mockery::mock(GitExec::class);
        $repository_destination = \Mockery::mock(\GitRepository::class);

        $dao->shouldReceive('createGitReferenceForPullRequest')->andReturns(1)->once();
        $dao->shouldReceive('updateGitReferenceToNextAvailableOne')->andReturns(2, 3)->twice();
        $executor_destination->shouldReceive('getReferencesFromPattern')->andReturns(
            ['refs/tlpr/1/head'],
            ['refs/tlpr/2/head'],
            []
        );
        $executor_source->shouldReceive('push')->once();

        $pull_request->shouldReceive('getId')->andReturns(1);
        $pull_request->shouldReceive('getSha1Src')->andReturns('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $repository_destination->shouldReceive('getPath')->andReturns('/path');

        $reference_creator->createPullRequestReference($pull_request, $executor_source, $executor_destination, $repository_destination);
    }

    public function testGitReferenceIsNotCreatedIfOptionIsNotEnabled()
    {
        $dao               = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao);

        $pull_request           = \Mockery::mock(PullRequest::class);
        $executor_source        = \Mockery::mock(GitExec::class);
        $executor_source->shouldNotReceive('push');
        $executor_destination   = \Mockery::mock(GitExec::class);
        $repository_destination = \Mockery::mock(\GitRepository::class);

        $reference_creator->createPullRequestReference($pull_request, $executor_source, $executor_destination, $repository_destination);
    }
}
