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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Mockery;
use Tuleap\Gitlab\API\ClientWrapper;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Presenters\PostPushCommitBotCommentReferencePresenterBuilder;
use TemplateRendererFactory;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Presenters\PostPushCommitBotCommentReferencePresenter;
use Tuleap\Templating\TemplateCache;

class PostPushCommitBotCommenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var PostPushCommitBotCommenter
     */
    private $commenter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $client_wrapper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitCredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitWebhookData
     */
    private $webhook_data;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepository
     */
    private $gitlab_repository;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InstanceBaseURLBuilder
     */
    private $bot_comment_reference_presenter_builder;
    /**
     * @var TemplateRendererFactory
     */
    private $template_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client_wrapper        = Mockery::mock(ClientWrapper::class);
        $this->credentials_retriever = Mockery::mock(PostPushCommitCredentialsRetriever::class);
        $this->logger                = Mockery::mock(LoggerInterface::class);
        $this->webhook_data          = Mockery::mock(PostPushCommitWebhookData::class);
        $this->gitlab_repository     = Mockery::mock(GitlabRepository::class);

        $this->bot_comment_reference_presenter_builder = Mockery::mock(PostPushCommitBotCommentReferencePresenterBuilder::class);

        $template_cache         = \Mockery::mock(TemplateCache::class, ['getPath' => null]);
        $this->template_factory = new TemplateRendererFactory($template_cache);

        $this->commenter = new PostPushCommitBotCommenter(
            $this->client_wrapper,
            $this->credentials_retriever,
            $this->logger,
            $this->bot_comment_reference_presenter_builder,
            $this->template_factory
        );
    }

    public function testNothingHappenIfNoReferences(): void
    {
        $this->credentials_retriever->shouldReceive('getCredentials')->never();
        $this->logger->shouldReceive('debug')->never();
        $this->client_wrapper->shouldReceive('postUrl')->never();

        $this->commenter->addCommentOnCommit($this->webhook_data, $this->gitlab_repository, []);
    }

    public function testNothingHappenIfNoCredentialsRetrieved(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->once();

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment can't be add on commit #azer12563 because there is no bot API token.")
            ->once();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturnNull()
            ->once();

        $this->client_wrapper->shouldReceive('postUrl')->never();

        $this->commenter->addCommentOnCommit($this->webhook_data, $this->gitlab_repository, [new WebhookTuleapReference(123)]);
    }

    public function testClientWrapperThrowErrorAndLogIt(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->twice();

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = new Credentials("https://example.fr", new ConcealedString("My_Token"));

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturn($credentials)
            ->once();

        $references = [
            new WebhookTuleapReference(123),
            new WebhookTuleapReference(59)
        ];

        $references_presenter = [
            new PostPushCommitBotCommentReferencePresenter(123, "https://example.fr"),
            new PostPushCommitBotCommentReferencePresenter(59, "https://example.fr")
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/repository/commits/azer12563/comments";
        $comment = "\nThis commit references:\n * [TULEAP-123](https://example.fr)\n * [TULEAP-59](https://example.fr)\n";

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ["note" => $comment])
            ->once()
            ->andThrow(new GitlabRequestException("404", "not found"));

        $this->logger
            ->shouldReceive('error')
            ->with("An error occurred during automatically comment commit #azer12563")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("|  |_Error returned by the GitLab server: not found")
            ->once();

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }

    public function testPOSTCommentOnCommit(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->twice();

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = new Credentials("https://example.fr", new ConcealedString("My_Token"));

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturn($credentials)
            ->once();

        $references = [
            new WebhookTuleapReference(123),
        ];

        $references_presenter = [
            new PostPushCommitBotCommentReferencePresenter(123, "https://example.fr"),
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/repository/commits/azer12563/comments";
        $comment = "This commit references: [TULEAP-123](https://example.fr).\n";

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ["note" => $comment])
            ->once();

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment was successfully added on commit #azer12563")
            ->once();

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }
}
