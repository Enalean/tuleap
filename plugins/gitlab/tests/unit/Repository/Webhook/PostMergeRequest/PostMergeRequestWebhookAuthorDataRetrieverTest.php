<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

class PostMergeRequestWebhookAuthorDataRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var PostMergeRequestWebhookAuthorDataRetriever
     */
    private $author_retriever;

    protected function setUp(): void
    {
        $this->credentials_retriever = Mockery::mock(CredentialsRetriever::class);
        $this->gitlab_api_client     = Mockery::mock(ClientWrapper::class);

        $this->author_retriever = new PostMergeRequestWebhookAuthorDataRetriever(
            $this->gitlab_api_client,
            $this->credentials_retriever,
        );
    }

    public function testItReturnsNullIfNoCredentials(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($integration)
            ->andReturn(null)
            ->once();

        $author = $this->author_retriever->retrieveAuthorData($integration, $merge_request_webhook_data);

        $this->assertNull($author);
    }

    public function testGitlabApiClientIsCallToGetAuthor(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($integration)
            ->andReturn($credentials)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('getUrl')
            ->with($credentials, '/users/10')
            ->andReturn(['name' => 'John', 'email' => 'john@thewall.fr'])
            ->once();

        $author = $this->author_retriever->retrieveAuthorData($integration, $merge_request_webhook_data);

        $this->assertEquals(['name' => 'John', 'email' => 'john@thewall.fr'], $author);
    }
}
