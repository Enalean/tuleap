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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;

require_once __DIR__ . '/../bootstrap.php';

class ReferenceFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReferenceFactory
     */
    private $reference_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\PullRequest\Factory
     */
    private $pull_request_factory;

    /**
     * @var \GitRepositoryFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $repository_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectReferenceRetriever
     */
    private $reference_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HTMLURLBuilder
     */
    private $html_url_builder;

    /**
     * @var \GitRepository|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $repository;

    /**
     * @var PullRequest
     */
    private $pull_request;

    public function setUp(): void
    {
        parent::setUp();
        $this->pull_request_factory = \Mockery::spy(\Tuleap\PullRequest\Factory::class);
        $this->repository_factory   = \Mockery::spy(\GitRepositoryFactory::class);
        $this->reference_retriever  = \Mockery::spy(\Tuleap\PullRequest\Reference\ProjectReferenceRetriever::class);
        $this->html_url_builder     = \Mockery::spy(\Tuleap\PullRequest\Reference\HTMLURLBuilder::class);

        $this->reference_factory = new ReferenceFactory(
            $this->pull_request_factory,
            $this->repository_factory,
            $this->reference_retriever,
            $this->html_url_builder
        );

        $this->pull_request = new PullRequest(1, '', '', 42, 101, '', '', '', '', '', '');

        $project            = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $this->repository   = Mockery::mock(\GitRepository::class);
        $this->repository->shouldReceive('getProject')->andReturn($project);
        $this->repository->shouldReceive('getProjectId')->andReturn(101);
    }

    public function testItCreatesAReference(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(1)->andReturns($this->pull_request);
        $this->repository_factory->shouldReceive('getRepositoryById')->with(42)->andReturns($this->repository);
        $this->reference_retriever->shouldReceive('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->andReturns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNotNull($reference);
        $this->assertInstanceOf(Reference::class, $reference);
    }

    public function testItDoesNotCreateAReferenceIfPullRequestIdNotExisting(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(1)->andThrows(new PullRequestNotFoundException());
        $this->repository_factory->shouldReceive('getRepositoryById')->with(42)->andReturns($this->repository);
        $this->reference_retriever->shouldReceive('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->andReturns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfRepositoryDoesNotExistAnymore(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(1)->andReturns($this->pull_request);
        $this->repository_factory->shouldReceive('getRepositoryById')->with(42)->andReturns(null);
        $this->reference_retriever->shouldReceive('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->andReturns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }

    public function testItDoesNotCreateAReferenceIfReferenceAlreadyExistInProject(): void
    {
        $keyword         = 'pr';
        $pull_request_id = 1;

        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(1)->andReturns($this->pull_request);
        $this->repository_factory->shouldReceive('getRepositoryById')->with(42)->andReturns($this->repository);
        $this->reference_retriever->shouldReceive('doesProjectReferenceWithKeywordExists')->with($keyword, 101)->andReturns(true);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }
}
