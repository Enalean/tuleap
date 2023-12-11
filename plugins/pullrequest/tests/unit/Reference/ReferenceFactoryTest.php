<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ReferenceFactoryTest extends TestCase
{
    private GitRepositoryFactory&MockObject $repository_factory;
    private MockObject&ProjectReferenceRetriever $reference_retriever;
    private MockObject&HTMLURLBuilder $html_url_builder;
    private GitRepository&MockObject $repository;
    private SearchPullRequestStub $pull_request_dao;

    public function setUp(): void
    {
        $this->repository_factory  = $this->createMock(GitRepositoryFactory::class);
        $this->reference_retriever = $this->createMock(ProjectReferenceRetriever::class);
        $this->html_url_builder    = $this->createMock(HTMLURLBuilder::class);

        $pull_request           = PullRequestTestBuilder::aPullRequestInReview()->withId(1)->withRepositoryId(42)->createdBy(101)->build();
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getProject')->willReturn($project);
        $this->repository->method('getProjectId')->willReturn(101);
    }

    private function getReferenceByPullRequestId(string $keyword, int $pull_request_id): ?\Reference
    {
        $reference_factory = new ReferenceFactory(
            new PullRequestRetriever($this->pull_request_dao),
            $this->repository_factory,
            $this->reference_retriever,
            $this->html_url_builder
        );

        return $reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);
    }

    public function testItCreatesAReference(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);
        $this->html_url_builder->method('getPullRequestOverviewUrl')->willReturn('');

        $reference = $this->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNotNull($reference);
        self::assertInstanceOf(Reference::class, $reference);
    }

    public function testItDoesNotCreateAReferenceIfPullRequestIdNotExisting(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_dao = SearchPullRequestStub::withNoRow();
        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);

        $reference = $this->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfRepositoryDoesNotExistAnymore(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn(null);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);

        $reference = $this->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfReferenceAlreadyExistInProject(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(true);

        $reference = $this->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }
}
