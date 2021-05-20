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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use TemplateRendererFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenter;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenterBuilder;
use Tuleap\Gitlab\Repository\Webhook\Bot\CommentSender;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\InvalidCredentialsNotifier;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Templating\TemplateCache;

class PostMergeRequestBotCommenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var PostMergeRequestBotCommenter
     */
    private $commenter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $client_wrapper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostMergeRequestWebhookData
     */
    private $webhook_data;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegration
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
        $this->credentials_retriever = Mockery::mock(CredentialsRetriever::class);
        $this->logger                = Mockery::mock(LoggerInterface::class);
        $this->webhook_data          = Mockery::mock(PostMergeRequestWebhookData::class);
        $this->gitlab_repository     = Mockery::mock(GitlabRepositoryIntegration::class);

        $this->bot_comment_reference_presenter_builder = Mockery::mock(BotCommentReferencePresenterBuilder::class);

        $template_cache         = \Mockery::mock(TemplateCache::class, ['getPath' => null]);
        $this->template_factory = new TemplateRendererFactory($template_cache);

        $this->commenter = new PostMergeRequestBotCommenter(
            new CommentSender($this->client_wrapper, Mockery::mock(InvalidCredentialsNotifier::class)),
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

        $this->commenter->addCommentOnMergeRequest($this->webhook_data, $this->gitlab_repository, []);
    }

    public function testNothingHappenIfNoCredentialsRetrieved(): void
    {
        $this->webhook_data
            ->shouldReceive("getMergeRequestId")
            ->andReturn("42");

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment can't be added on merge request #42 because there is no bot API token.")
            ->once();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturnNull()
            ->once();

        $this->client_wrapper->shouldReceive('postUrl')->never();

        $this->commenter->addCommentOnMergeRequest($this->webhook_data, $this->gitlab_repository, [new WebhookTuleapReference(123)]);
    }

    public function testClientWrapperThrowErrorAndLogIt(): void
    {
        $this->webhook_data
            ->shouldReceive("getMergeRequestId")
            ->andReturn("42");

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = CredentialsTestBuilder::get()->build();

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
            new BotCommentReferencePresenter(123, "https://example.fr"),
            new BotCommentReferencePresenter(59, "https://example.fr")
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/merge_requests/42/notes";
        $comment = <<<EOS

            This merge request references:

             * [TULEAP-123](https://example.fr)
             * [TULEAP-59](https://example.fr)


            EOS;

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ["body" => $comment])
            ->once()
            ->andThrow(new GitlabRequestException("404", "not found"));

        $this->logger
            ->shouldReceive('error')
            ->with("An error occurred during automatically comment merge request #42")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("|  |_Error returned by the GitLab server: not found")
            ->once();

        $this->commenter->addCommentOnMergeRequest(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }

    public function testPOSTCommentOnCommit(): void
    {
        $this->webhook_data
            ->shouldReceive("getMergeRequestId")
            ->andReturn("42");

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturn($credentials)
            ->once();

        $references = [
            new WebhookTuleapReference(123),
        ];

        $references_presenter = [
            new BotCommentReferencePresenter(123, "https://example.fr"),
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/merge_requests/42/notes";
        $comment = <<<EOS

            This merge request references: [TULEAP-123](https://example.fr).


            EOS;

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ['body' => $comment])
            ->once();

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment was successfully added on merge request #42")
            ->once();

        $this->commenter->addCommentOnMergeRequest(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }
}
