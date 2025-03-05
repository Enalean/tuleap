<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Repository\View;

use Git_GitRepositoryUrlManager;
use GitRepository;
use HTTPRequest;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Head;
use Tuleap\Git\GitPHP\Project;
use Tuleap\Git\GitPHP\Tag;
use Tuleap\Git\Repository\GitPHPProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilesHeaderPresenterBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private FilesHeaderPresenterBuilder $builder;
    private HTTPRequest $request;
    private GitRepository&MockObject $repository;
    private CommitForCurrentTreeRetriever&MockObject $commit_retriever;
    private GitPHPProjectRetriever&MockObject $gitphp_project_retriever;
    private Project&MockObject $gitphp_project;

    protected function setUp(): void
    {
        $this->request                  = new HTTPRequest();
        $this->repository               = $this->createMock(GitRepository::class);
        $this->gitphp_project           = $this->createMock(Project::class);
        $this->commit_retriever         = $this->createMock(CommitForCurrentTreeRetriever::class);
        $this->gitphp_project_retriever = $this->createMock(GitPHPProjectRetriever::class);

        $this->repository->method('getId')->willReturn(123);
        $this->repository->method('getFullPath')->willReturn(vfsStream::setup()->url());

        $project = ProjectTestBuilder::aProject()->withId(42)->build();
        $this->request->setProject($project);

        HTTPRequest::setInstance($this->request);

        $url_manager = $this->createMock(Git_GitRepositoryUrlManager::class);
        $url_manager->method('getRepositoryBaseUrl');

        $this->builder = new FilesHeaderPresenterBuilder(
            $this->gitphp_project_retriever,
            $this->commit_retriever,
            $url_manager
        );
    }

    protected function tearDown(): void
    {
        HTTPRequest::clearInstance();
    }

    public function testHeadNameIsFirstBranchName(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $second_head = $this->createMock(Head::class);
        $second_head->method('GetName')->willReturn('another');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head, $second_head]);
        $commit->method('GetTags')->willReturn([]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('dev', $presenter->head_name);
        self::assertFalse($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsFirstTagNameIfNoBranch(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');
        $second_tag = $this->createMock(Tag::class);
        $second_tag->method('GetName')->willReturn('v12-1');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([]);
        $commit->method('GetTags')->willReturn([$first_tag, $second_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('v12', $presenter->head_name);
        self::assertTrue($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsRequestedRef(): void
    {
        $this->request->params = ['a' => 'tree', 'hb' => 'v12-1'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');
        $second_tag = $this->createMock(Tag::class);
        $second_tag->method('GetName')->willReturn('v12-1');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head]);
        $commit->method('GetTags')->willReturn([$first_tag, $second_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('v12-1', $presenter->head_name);
        self::assertTrue($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testCurrentURLParamatersExceptHashbaseAndHashbaseArePassedAsArrayToTheSelector(): void
    {
        $this->request->params = [
            'a'  => 'blame',
            'hb' => 'v12-1',
            'h'  => 'd3c5d469b37586aa924577054162c31b6bf03a9a',
            'f'  => 'app.js',
        ];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');
        $second_tag = $this->createMock(Tag::class);
        $second_tag->method('GetName')->willReturn('v12-1');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head]);
        $commit->method('GetTags')->willReturn([$first_tag, $second_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('v12-1', $presenter->head_name);
        self::assertTrue($presenter->is_tag);

        $parameters = json_decode($presenter->json_encoded_parameters, true);
        self::assertEquals('blame', $parameters['a']);
        self::assertEquals('app.js', $parameters['f']);
        self::assertFalse(isset($parameters['hb']));
        self::assertFalse(isset($parameters['h']));
    }

    public function testTreeIsDefaultViewIfRequestIsEmpty(): void
    {
        $this->request->params = [];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');
        $second_tag = $this->createMock(Tag::class);
        $second_tag->method('GetName')->willReturn('v12-1');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head]);
        $commit->method('GetTags')->willReturn([$first_tag, $second_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('dev', $presenter->head_name);

        $parameters = json_decode($presenter->json_encoded_parameters, true);
        self::assertEquals('tree', $parameters['a']);
    }

    public function testHeadNameIsRequestedRefEvenIfFullyQualifiedTag(): void
    {
        $this->request->params = ['a' => 'tree', 'hb' => 'refs/tags/v12-1'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');
        $second_tag = $this->createMock(Tag::class);
        $second_tag->method('GetName')->willReturn('v12-1');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head]);
        $commit->method('GetTags')->willReturn([$first_tag, $second_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('v12-1', $presenter->head_name);
        self::assertTrue($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsRequestedRefEvenIfFullyQualifiedBranch(): void
    {
        $this->request->params = ['a' => 'tree', 'hb' => 'refs/heads/feature'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $first_head = $this->createMock(Head::class);
        $first_head->method('GetName')->willReturn('dev');
        $second_head = $this->createMock(Head::class);
        $second_head->method('GetName')->willReturn('feature');
        $first_tag = $this->createMock(Tag::class);
        $first_tag->method('GetName')->willReturn('v12');

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([$first_head, $second_head]);
        $commit->method('GetTags')->willReturn([$first_tag]);
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('feature', $presenter->head_name);
        self::assertFalse($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsHashIfNoBranchNorTag(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([]);
        $commit->method('GetTags')->willReturn([]);
        $commit->method('GetHash')->willReturn('a1b2c3d4e5f6');
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertEquals('a1b2c3d4e5f6', $presenter->head_name);
        self::assertFalse($presenter->is_tag);
        self::assertEquals(12345, $presenter->committer_epoch);
    }

    public function testHeadNameIsUndefinedIfNoCommitForCurrentTreeButThereIsAnExistingRefInTheRepository(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);
        $this->gitphp_project->method('GetRefs')->willReturn([$this->createMock(Head::class)]);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn(null);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertTrue($presenter->can_display_selector);
        self::assertTrue($presenter->is_undefined);
        self::assertEquals('Undefined', $presenter->head_name);
        self::assertFalse($presenter->is_tag);
        self::assertEquals('', $presenter->committer_epoch);
    }

    public function testSelectorIsNotDisplayedIfNoCommitForCurrentTreeAndNoRefInTheRepository(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);
        $this->gitphp_project->method('GetRefs')->willReturn([]);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn(null);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertFalse($presenter->can_display_selector);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideActionsThatShouldNotDisplayTheSelector')]
    public function testSelectorIsNotDisplayedIfWeAreOnACommitView($action): void
    {
        $this->request->params = ['a' => $action];

        $this->repository->method('isCreated')->willReturn(true);
        $this->gitphp_project_retriever->method('getFromRepository')
            ->with($this->repository)
            ->willReturn($this->gitphp_project);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHeads')->willReturn([]);
        $commit->method('GetTags')->willReturn([]);
        $commit->method('GetHash')->willReturn('a1b2c3d4e5f6');
        $commit->method('GetCommitterEpoch')->willReturn(12345);

        $this->commit_retriever->method('getCommitOfCurrentTree')
            ->with($this->request, $this->gitphp_project)
            ->willReturn($commit);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertFalse($presenter->can_display_selector);
    }

    public static function provideActionsThatShouldNotDisplayTheSelector(): array
    {
        return [
            ['commit'],
            ['blobdiff'],
        ];
    }

    public function testSelectorIsNotDisplayedIfRepositoryIsNotCreated(): void
    {
        $this->request->params = ['a' => 'tree'];

        $this->repository->method('isCreated')->willReturn(false);

        $presenter = $this->builder->build($this->request, $this->repository);

        self::assertFalse($presenter->can_display_selector);
    }
}
