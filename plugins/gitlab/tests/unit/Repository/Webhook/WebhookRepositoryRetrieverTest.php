<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;

class WebhookRepositoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabRepository
     */
    private $gitlab_repository;

    /**
     * @var WebhookDataExtractor
     */
    private $repository_retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    /**
     * @var PostPushWebhookData
     */
    private $webhook_data;

    protected function setUp(): void
    {
        $this->gitlab_repository = new GitlabRepository(
            1,
            123456,
            'path/repo01',
            'description',
            'https://example.com/path/repo01',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory = Mockery::mock(GitlabRepositoryFactory::class);

        $this->repository_retriever = new WebhookRepositoryRetriever(
            $this->gitlab_repository_factory,
        );

        $this->webhook_data = new PostPushWebhookData(
            "push",
            123456,
            "https://example.com/path/repo01",
            []
        );
    }

    public function testItThrowsAnExceptionIfRepositoryNotFound(): void
    {
        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturnNull();

        $this->expectException(RepositoryNotFoundException::class);

        $this->repository_retriever->retrieveRepositoryFromWebhookData(
            $this->webhook_data
        );
    }

    public function testItReturnsTheGitlabRepository(): void
    {
        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturn($this->gitlab_repository);

        $repository = $this->repository_retriever->retrieveRepositoryFromWebhookData(
            $this->webhook_data
        );

        $this->assertSame(
            $this->gitlab_repository,
            $repository
        );
    }
}
