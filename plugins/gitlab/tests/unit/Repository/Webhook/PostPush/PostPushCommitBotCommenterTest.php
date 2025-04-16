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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

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
use Tuleap\Templating\TemplateCache;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PostPushCommitBotCommenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientWrapper
     */
    private $client_wrapper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushCommitWebhookData
     */
    private $webhook_data;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegration
     */
    private $gitlab_repository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BotCommentReferencePresenterBuilder
     */
    private $bot_comment_reference_presenter_builder;

    private TemplateRendererFactory $template_factory;
    private PostPushCommitBotCommenter $commenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client_wrapper                          = $this->createMock(ClientWrapper::class);
        $this->credentials_retriever                   = $this->createMock(CredentialsRetriever::class);
        $this->logger                                  = $this->createMock(LoggerInterface::class);
        $this->webhook_data                            = $this->createMock(PostPushCommitWebhookData::class);
        $this->gitlab_repository                       = $this->createMock(GitlabRepositoryIntegration::class);
        $this->bot_comment_reference_presenter_builder = $this->createMock(BotCommentReferencePresenterBuilder::class);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $this->template_factory = new TemplateRendererFactory($template_cache);

        $this->commenter = new PostPushCommitBotCommenter(
            new CommentSender($this->client_wrapper, $this->createMock(InvalidCredentialsNotifier::class)),
            $this->credentials_retriever,
            $this->logger,
            $this->bot_comment_reference_presenter_builder,
            $this->template_factory
        );
    }

    public function testNothingHappenIfNoReferences(): void
    {
        $this->credentials_retriever
            ->expects($this->never())
            ->method('getCredentials');

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->client_wrapper
            ->expects($this->never())
            ->method('postUrl');

        $this->commenter->addCommentOnCommit($this->webhook_data, $this->gitlab_repository, []);
    }

    public function testNothingHappenIfNoCredentialsRetrieved(): void
    {
        $this->webhook_data
            ->expects($this->once())
            ->method('getSha1')
            ->willReturn('azer12563');

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with("Comment can't be added on commit #azer12563 because there is no bot API token.");

        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($this->gitlab_repository)
            ->willReturn(null);

        $this->client_wrapper
            ->expects($this->never())
            ->method('postUrl');

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            [new WebhookTuleapReference(123, null)]
        );
    }

    public function testClientWrapperThrowErrorAndLogIt(): void
    {
        $this->webhook_data
            ->expects($this->exactly(2))
            ->method('getSha1')
            ->willReturn('azer12563');

        $this->gitlab_repository
            ->expects($this->once())
            ->method('getGitlabRepositoryId')
            ->willReturn(4);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($this->gitlab_repository)
            ->willReturn($credentials);

        $references = [
            new WebhookTuleapReference(123, null),
            new WebhookTuleapReference(59, null),
        ];

        $references_presenter = [
            new BotCommentReferencePresenter(123, 'https://example.fr'),
            new BotCommentReferencePresenter(59, 'https://example.fr'),
        ];

        $this->bot_comment_reference_presenter_builder
            ->expects($this->once())
            ->method('build')
            ->with($references)
            ->willReturn($references_presenter);

        $url     = '/projects/4/repository/commits/azer12563/comments';
        $comment = "\nThis commit references:\n * [TULEAP-123](https://example.fr)\n * [TULEAP-59](https://example.fr)\n";

        $this->client_wrapper
            ->expects($this->once())
            ->method('postUrl')
            ->with($credentials, $url, ['note' => $comment])
            ->willThrowException(new GitlabRequestException(404, 'not found'));

        $this->logger
            ->method('error')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        'An error occurred during automatically comment commit #azer12563',
                        '|  |_Error returned by the GitLab server: not found' => true,
                    };
                }
            );

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }

    public function testPOSTCommentOnCommit(): void
    {
        $this->webhook_data
            ->expects($this->exactly(2))
            ->method('getSha1')
            ->willReturn('azer12563');

        $this->gitlab_repository
            ->expects($this->once())
            ->method('getGitlabRepositoryId')
            ->willReturn(4);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($this->gitlab_repository)
            ->willReturn($credentials);

        $references = [
            new WebhookTuleapReference(123, null),
        ];

        $references_presenter = [
            new BotCommentReferencePresenter(123, 'https://example.fr'),
        ];

        $this->bot_comment_reference_presenter_builder
            ->expects($this->once())
            ->method('build')
            ->with($references)
            ->willReturn($references_presenter);

        $url     = '/projects/4/repository/commits/azer12563/comments';
        $comment = "This commit references: [TULEAP-123](https://example.fr).\n";

        $this->client_wrapper
            ->expects($this->once())
            ->method('postUrl')
            ->with($credentials, $url, ['note' => $comment]);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Comment was successfully added on commit #azer12563');

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }
}
