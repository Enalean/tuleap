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

use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Presenters\PostPushCommitBotCommentReferencePresenterBuilder;
use TemplateRendererFactory;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Presenters\PostPushCommitBotCommentPresenter;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PostPushCommitBotCommenter
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var PostPushCommitCredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PostPushCommitBotCommentReferencePresenterBuilder
     */
    private $bot_comment_reference_presenter_builder;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;

    public function __construct(
        ClientWrapper $gitlab_api_client,
        PostPushCommitCredentialsRetriever $credentials_retriever,
        LoggerInterface $logger,
        PostPushCommitBotCommentReferencePresenterBuilder $bot_comment_reference_presenter_builder,
        TemplateRendererFactory $template_renderer_factory
    ) {
        $this->gitlab_api_client                       = $gitlab_api_client;
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
        GitlabRepository $gitlab_repository,
        array $references
    ): void {
        if (count($references) === 0) {
            return;
        }

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository);

        if (! $credentials) {
            $this->logger->debug("Comment can't be add on commit #{$commit->getSha1()} because there is no bot API token.");
            return;
        }

        $reference_presenters = $this->bot_comment_reference_presenter_builder->build($references);

        $renderer = $this->template_renderer_factory->getRenderer(dirname(__FILE__) . '/../../../../templates');
        $comment  = $renderer->renderToString("gitlab-bot-comment", new PostPushCommitBotCommentPresenter($reference_presenters));

        try {
            $url = "/projects/{$gitlab_repository->getGitlabRepositoryId()}/repository/commits/{$commit->getSha1()}/comments";
            $this->gitlab_api_client->postUrl(
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
