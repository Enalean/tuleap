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
use Tuleap\Git\GitPHP\Project;
use Tuleap\Git\GitPHP\Tag;
use Tuleap\Git\Repository\GitPHPProjectRetriever;

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
    /**
     * @var GitPHPProjectRetriever
     */
    private $gitphp_project_retriever;
    /**
     * @var Project
     */
    private $gitphp_project;

    protected function setUp()
    {
        ForgeConfig::store();

        $this->request                  = Mockery::mock(HTTPRequest::class);
        $this->repository               = Mockery::mock(GitRepository::class);
        $this->gitphp_project           = Mockery::mock(Project::class);
        $this->commit_retriever         = Mockery::mock(CommitForCurrentTreeRetriever::class);
        $this->gitphp_project_retriever = Mockery::mock(GitPHPProjectRetriever::class);

        $this->repository->allows()->getId()->andReturns(123);

        $url_manager = Mockery::spy(Git_GitRepositoryUrlManager::class);

        $this->builder = new FilesHeaderPresenterBuilder(
            $this->gitphp_project_retriever,
            $this->commit_retriever,
            $url_manager
        );
    }

    protected function tearDown()
    {
        ForgeConfig::restore();
    }

    private function setRequest(array $parameters)
    {
        $possible_parameters = ['a', 'h', 'hb', 'f', 's', 'st', 'm'];
        foreach ($possible_parameters as $key) {
            if (isset($parameters[$key])) {
                $this->request->allows()->exist($key)->andReturn(true);
                $this->request->allows()->get($key)->andReturn($parameters[$key]);
            } else {
                $this->request->allows()->exist($key)->andReturn(false);
                $this->request->allows()->get($key)->andReturn(false);
            }
        }
    }

    public function testHeadNameIsFirstBranchName()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
                               ->with($this->request, $this->gitphp_project)
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
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
                               ->with($this->request, $this->gitphp_project)
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
        $this->setRequest(['a' => 'tree', 'hb' => 'v12-1']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
            ->with($this->request, $this->gitphp_project)
            ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('v12-1', $presenter->head_name);
        $this->assertTrue($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testCurrentURLParamatersExceptHashbaseAndHashbaseArePassedAsArrayToTheSelector()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest([
            'a' => 'blame',
            'hb' => 'v12-1',
            'h' => 'd3c5d469b37586aa924577054162c31b6bf03a9a',
            'f' => 'app.js'
        ]);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
            ->with($this->request, $this->gitphp_project)
            ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('v12-1', $presenter->head_name);
        $this->assertTrue($presenter->is_tag);

        $parameters = json_decode($presenter->json_encoded_parameters, true);
        $this->assertEquals('blame', $parameters['a']);
        $this->assertEquals('app.js', $parameters['f']);
        $this->assertFalse(isset($parameters['hb']));
        $this->assertFalse(isset($parameters['h']));
    }

    public function testTreeIsDefaultViewIfRequestIsEmpty()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest([]);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
            ->with($this->request, $this->gitphp_project)
            ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('dev', $presenter->head_name);

        $parameters = json_decode($presenter->json_encoded_parameters, true);
        $this->assertEquals('tree', $parameters['a']);
    }

    public function testHeadNameIsRequestedRefEvenIfFullyQualifiedTag()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => 'tree', 'hb' => 'refs/tags/v12-1']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
                               ->with($this->request, $this->gitphp_project)
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
        $this->setRequest(['a' => 'tree', 'hb' => 'refs/heads/feature']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

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
                               ->with($this->request, $this->gitphp_project)
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
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->gitphp_project)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertEquals('a1b2c3d4e5f6', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsUndefinedIfNoCommitForCurrentTreeButThereIsAnExistingRefInTheRepository()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
                                       ->getFromRepository()
                                       ->with($this->repository)
                                       ->andReturns($this->gitphp_project);
        $this->gitphp_project->allows()->GetRefs()->andReturns([Mockery::mock(Head::class)]);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->gitphp_project)
                               ->andReturn(null);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertTrue($presenter->can_display_selector);
        $this->assertTrue($presenter->is_undefined);
        $this->assertEquals('Undefined', $presenter->head_name);
        $this->assertFalse($presenter->is_tag);
        $this->assertEquals('', $presenter->committer_epoch);
    }

    public function testSelectorIsNotDisplayedIfNoCommitForCurrentTreeAndNoRefInTheRepository()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
                                       ->getFromRepository()
                                       ->with($this->repository)
                                       ->andReturns($this->gitphp_project);
        $this->gitphp_project->allows()->GetRefs()->andReturns([]);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->gitphp_project)
                               ->andReturn(null);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }

    public function testSelectorIsNotDisplayedIfConfigDisallowsIt()
    {
        ForgeConfig::set('git_repository_bp', '0');
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
                               ->getCommitOfCurrentTree()
                               ->with($this->request, $this->gitphp_project)
                               ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }

    /**
     * @dataProvider provideActionsThatShouldNotDisplayTheSelector
     */
    public function testSelectorIsNotDisplayedIfWeAreOnACommitView($action)
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => $action]);

        $this->repository->allows()->isCreated()->andReturns(true);
        $this->gitphp_project_retriever->allows()
            ->getFromRepository()
            ->with($this->repository)
            ->andReturns($this->gitphp_project);

        $commit = Mockery::mock(Commit::class);
        $commit->allows()->GetHeads()->andReturn([]);
        $commit->allows()->GetTags()->andReturn([]);
        $commit->allows()->GetHash()->andReturn('a1b2c3d4e5f6');
        $commit->allows()->GetCommitterEpoch()->andReturn(12345);

        $this->commit_retriever->allows()
            ->getCommitOfCurrentTree()
            ->with($this->request, $this->gitphp_project)
            ->andReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }

    public function provideActionsThatShouldNotDisplayTheSelector()
    {
        return [
            ['commit']
        ];
    }

    public function testSelectorIsNotDisplayedIfRepositoryIsNotCreated()
    {
        ForgeConfig::set('git_repository_bp', '1');
        $this->setRequest(['a' => 'tree']);

        $this->repository->allows()->isCreated()->andReturns(false);

        $presenter = $this->builder->build($this->request, $this->repository);

        $this->assertFalse($presenter->can_display_selector);
    }
}
