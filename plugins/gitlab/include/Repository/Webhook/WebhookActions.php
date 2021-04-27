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
use LogicException;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookData;

class WebhookActions
{
    /**
     * @var GitlabRepositoryDao
     */
    private $gitlab_repository_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PostPushWebhookActionProcessor
     */
    private $post_push_webhook_action_processor;
    /**
     * @var PostMergeRequestWebhookActionProcessor
     */
    private $post_merge_request_action_processor;
    /**
     * @var TagPushWebhookActionProcessor
     */
    private $tag_push_webhook_action_processor;

    public function __construct(
        GitlabRepositoryDao $gitlab_repository_dao,
        PostPushWebhookActionProcessor $post_push_webhook_action_processor,
        PostMergeRequestWebhookActionProcessor $post_merge_request_action_processor,
        TagPushWebhookActionProcessor $tag_push_webhook_action_processor,
        LoggerInterface $logger
    ) {
        $this->gitlab_repository_dao               = $gitlab_repository_dao;
        $this->post_push_webhook_action_processor  = $post_push_webhook_action_processor;
        $this->post_merge_request_action_processor = $post_merge_request_action_processor;
        $this->tag_push_webhook_action_processor   = $tag_push_webhook_action_processor;
        $this->logger                              = $logger;
    }

    /**
     * @throws LogicException
     */
    public function performActions(
        GitlabRepository $gitlab_repository,
        WebhookData $webhook_data,
        DateTimeImmutable $webhook_reception_date
    ): void {
        $this->checkWebhookDataIsSupported($webhook_data);
        $this->updateLastPushDateForRepository($gitlab_repository, $webhook_reception_date);

        if ($webhook_data instanceof PostPushWebhookData) {
            $this->post_push_webhook_action_processor->process($gitlab_repository, $webhook_data);
        }

        if ($webhook_data instanceof PostMergeRequestWebhookData) {
            $this->post_merge_request_action_processor->process($gitlab_repository, $webhook_data);
        }

        if ($webhook_data instanceof TagPushWebhookData) {
            $this->tag_push_webhook_action_processor->process($gitlab_repository, $webhook_data);
        }
    }

    private function updateLastPushDateForRepository(
        GitlabRepository $gitlab_repository,
        DateTimeImmutable $webhook_reception_date
    ): void {
        $this->gitlab_repository_dao->updateLastPushDateForRepository(
            $gitlab_repository->getId(),
            $webhook_reception_date->getTimestamp()
        );
        $this->logger->info(
            "Last update date successfully updated for GitLab repository #" . $gitlab_repository->getId()
        );
    }

    private function checkWebhookDataIsSupported(WebhookData $webhook_data): void
    {
        if ($webhook_data instanceof PostPushWebhookData) {
            return;
        }

        if ($webhook_data instanceof PostMergeRequestWebhookData) {
            return;
        }

        if ($webhook_data instanceof TagPushWebhookData) {
            return;
        }

        $message = "The provided webhook type " . $webhook_data->getEventName() . " is unknown";
        $this->logger->error($message);

        throw new LogicException($message);
    }
}
