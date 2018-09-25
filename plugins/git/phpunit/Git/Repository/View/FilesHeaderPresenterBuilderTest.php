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

namespace Tuleap\Git\Repository\View;

use ForgeConfig;
use Git_GitRepositoryUrlManager;
use GitRepository;
use HTTPRequest;
use Mockery;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Head;
use Tuleap\Git\GitPHP\Tag;

class FilesHeaderPresenterBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilesHeaderPresenterBuilder
     */
    private $builder;
    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var GitRepository
     */
    private $repository;
    /**
     * @var CommitForCurrentTreeRetriever
     */
    private $commit_retriever;

    protected function setUp()
    {
        ForgeConfig::store();

        $this->request          = Mockery::mock(HTTPRequest::class);
        $this->repository       = Mockery::mock(GitRepository::class);
        $this->commit_retriever = Mockery::mock(CommitForCurrentTreeRetriever::class);

        $this->repository->allows()->getId()->andReturns(123);

        $url_manager = Mockery::spy(Git_GitRepositoryUrlManager::class);

        $this->builder = new FilesHeaderPresenterBuilder($this->commit_retriever, $url_manager);
    }

    protected function tearDown()
    {
        ForgeConfig::restore();
    }

    public function testHeadNameIsFirstBranchName()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn(false);

        $this->repository->allows()->isCreated()->andReturns(true);

        $first_head = Mockery::mock(Head::class);
        $first_head->allows()->GetName()->andReturn('dev');
        $second_head = Mockery::mock(Head::class);
        $second_head->allows()->GetName()->andReturn('another');

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([$first_head, $second_head]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('dev', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsFirstTagNameIfNoBranch()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn(false);

        $this->repository->allows()->isCreated()->andReturns(true);

        $first_tag = Mockery::mock(Tag::class);
        $first_tag->allows()->GetName()->andReturn('v12');
        $second_tag = Mockery::mock(Tag::class);
        $second_tag->allows()->GetName()->andReturn('v12-1');

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([$first_tag, $second_tag]);
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('v12', $presenter->head_name);
        $this->assertTrue($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsRequestedRef()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn('v12-1');

        $this->repository->allows()->isCreated()->andReturns(true);

        $first_head = Mockery::mock(Head::class);
        $first_head->allows()->GetName()->andReturn('dev');
        $first_tag = Mockery::mock(Tag::class);
        $first_tag->allows()->GetName()->andReturn('v12');
        $second_tag = Mockery::mock(Tag::class);
        $second_tag->allows()->GetName()->andReturn('v12-1');

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([$first_head]);
        $commit->allows()->GetTags()->andReturn([$first_tag, $second_tag]);
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('v12-1', $presenter->head_name);
        $this->assertTrue($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsRequestedRefEvenIfFullyQualifiedTag()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn('refs/tags/v12-1');

        $this->repository->allows()->isCreated()->andReturns(true);

        $first_head = Mockery::mock(Head::class);
        $first_head->allows()->GetName()->andReturn('dev');
        $first_tag = Mockery::mock(Tag::class);
        $first_tag->allows()->GetName()->andReturn('v12');
        $second_tag = Mockery::mock(Tag::class);
        $second_tag->allows()->GetName()->andReturn('v12-1');

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([$first_head]);
        $commit->allows()->GetTags()->andReturn([$first_tag, $second_tag]);
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('v12-1', $presenter->head_name);
        $this->assertTrue($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsRequestedRefEvenIfFullyQualifiedBranch()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn('refs/heads/feature');

        $this->repository->allows()->isCreated()->andReturns(true);

        $first_head = Mockery::mock(Head::class);
        $first_head->allows()->GetName()->andReturn('dev');
        $second_head = Mockery::mock(Head::class);
        $second_head->allows()->GetName()->andReturn('feature');
        $first_tag = Mockery::mock(Tag::class);
        $first_tag->allows()->GetName()->andReturn('v12');

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([$first_head, $second_head]);
        $commit->allows()->GetTags()->andReturn([$first_tag]);
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('feature', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsHashIfNoBranchNorTag()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');
        $this->request->allows()->get('hb')->andReturn(false);

        $this->repository->allows()->isCreated()->andReturns(true);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('a1b2c3d4e5f6', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsUndefinedIfNoCommitForCurrentTree()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');

        $this->repository->allows()->isCreated()->andReturns(true);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn(null);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertTrue($presenter->is_undefined);
        $this->assertEquals('Undefined', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals('', $presenter->committer_epoch);
    }

    public function testSelectorIsNotDisplayedIfConfigDisallowsIt()
    {
        ForgeConfig::set('git_repository_bp', '0');
        $this->request->allows()->get('a')->andReturn('tree');

        $this->repository->allows()->isCreated()->andReturns(true);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }

    public function testSelectorIsNotDisplayedIfWeAreNotOnATree()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('commit');

        $this->repository->allows()->isCreated()->andReturns(true);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->repository)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }

    public function testSelectorIsNotDisplayedIfRepositoryIsNotCreated()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->request->allows()->get('a')->andReturn('tree');

        $this->repository->allows()->isCreated()->andReturns(false);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }
}
