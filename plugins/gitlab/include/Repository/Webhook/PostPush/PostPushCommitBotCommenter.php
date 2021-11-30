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
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentPresenter;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenterBuilder;
use Tuleap\Gitlab\Repository\Webhook\Bot\CommentSender;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PostPushCommitBotCommenter
{
    /**
     * @var CommentSender
     */
    private $comment_sender;
    /**
     * @var CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var BotCommentReferencePresenterBuilder
     */
    private $bot_comment_reference_presenter_builder;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;

    public function __construct(
        CommentSender $comment_sender,
        CredentialsRetriever $credentials_retriever,
        LoggerInterface $logger,
        BotCommentReferencePresenterBuilder $bot_comment_reference_presenter_builder,
        TemplateRendererFactory $template_renderer_factory,
    ) {
        $this->comment_sender                          = $comment_sender;
        $this->credentials_retriever                   = $credentials_retriever;
        $this->logger                                  = $logger;
        $this->bot_comment_reference_presenter_builder = $bot_comment_reference_presenter_builder;
        $this->template_renderer_factory               = $template_renderer_factory;
    }

    /**
     * @param WebhookTuleapReference[] $references
     */
    public function addCommentOnCommit(
        PostPushCommitWebhookData $commit,
        GitlabRepositoryIntegration $gitlab_repository_integration,
        array $references,
    ): void {
        if (count($references) === 0) {
            return;
        }

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository_integration);

        if (! $credentials) {
            $this->logger->debug("Comment can't be added on commit #{$commit->getSha1()} because there is no bot API token.");
            return;
        }

        $reference_presenters = $this->bot_comment_reference_presenter_builder->build($references);

        $renderer = $this->template_renderer_factory->getRenderer(dirname(__FILE__) . '/../../../../templates');
        $comment  = $renderer->renderToString("gitlab-bot-comment-commit", new BotCommentPresenter($reference_presenters));

        try {
            $url = "/projects/{$gitlab_repository_integration->getGitlabRepositoryId()}/repository/commits/{$commit->getSha1()}/comments";
            $this->comment_sender->sendComment(
                $gitlab_repository_integration,
                $credentials,
                $url,
                ["note" => $comment]
            );

            $this->logger->debug("Comment was successfully added on commit #{$commit->getSha1()}");
        } catch (GitlabRequestException | GitlabResponseAPIException $request_exception) {
            $this->logger->error("An error occurred during automatically comment commit #{$commit->getSha1()}");
            $this->logger->error("|  |_{$request_exception->getMessage()}");
        }
    }
}
