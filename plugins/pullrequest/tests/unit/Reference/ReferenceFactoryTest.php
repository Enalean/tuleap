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

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ReferenceFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferenceFactory $reference_factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\PullRequest\Factory
     */
    private $pull_request_factory;

    /**
     * @var \GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectReferenceRetriever
     */
    private $reference_retriever;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HTMLURLBuilder
     */
    private $html_url_builder;

    /**
     * @var \GitRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    private PullRequest $pull_request;

    public function setUp(): void
    {
        parent::setUp();
        $this->pull_request_factory = $this->createMock(\Tuleap\PullRequest\Factory::class);
        $this->repository_factory   = $this->createMock(\GitRepositoryFactory::class);
        $this->reference_retriever  = $this->createMock(\Tuleap\PullRequest\Reference\ProjectReferenceRetriever::class);
        $this->html_url_builder     = $this->createMock(\Tuleap\PullRequest\Reference\HTMLURLBuilder::class);

        $this->reference_factory = new ReferenceFactory(
            $this->pull_request_factory,
            $this->repository_factory,
            $this->reference_retriever,
            $this->html_url_builder
        );

        $this->pull_request = new PullRequest(1, '', '', 42, 101, '', '', '', '', '', '', Comment::FORMAT_TEXT);

        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->repository = $this->createMock(\GitRepository::class);
        $this->repository->method('getProject')->willReturn($project);
        $this->repository->method('getProjectId')->willReturn(101);
    }

    public function testItCreatesAReference(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->method('getPullRequestById')->with(1)->willReturn($this->pull_request);
        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);
        $this->html_url_builder->method('getPullRequestOverviewUrl')->willReturn('');

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNotNull($reference);
        self::assertInstanceOf(Reference::class, $reference);
    }

    public function testItDoesNotCreateAReferenceIfPullRequestIdNotExisting(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->method('getPullRequestById')->with(1)->willThrowException(new PullRequestNotFoundException());
        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfRepositoryDoesNotExistAnymore(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->method('getPullRequestById')->with(1)->willReturn($this->pull_request);
        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn(null);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfReferenceAlreadyExistInProject(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->method('getPullRequestById')->with(1)->willReturn($this->pull_request);
        $this->repository_factory->method('getRepositoryById')->with(42)->willReturn($this->repository);
        $this->reference_retriever->method('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->willReturn(true);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        self::assertNull($reference);
    }
}
