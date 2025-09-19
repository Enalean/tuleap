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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookDataBuilder;

class WebhookDataExtractor
{
    private const string EVENT_HEADER        = 'X-Gitlab-Event';
    private const string PROJECT_KEY         = 'project';
    private const string PROJECT_ID_KEY      = 'id';
    private const string PROJECT_URL_KEY     = 'web_url';
    private const string PUSH_EVENT          = 'Push Hook';
    private const string MERGE_REQUEST_EVENT = 'Merge Request Hook';
    private const string TAG_PUSH_EVENT      = 'Tag Push Hook';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PostMergeRequestWebhookDataBuilder
     */
    private $post_merge_request_webhook_data_builder;
    /**
     * @var PostPushWebhookDataBuilder
     */
    private $post_push_webhook_data_builder;
    /**
     * @var TagPushWebhookDataBuilder
     */
    private $tag_push_webhook_data_builder;

    public function __construct(
        PostPushWebhookDataBuilder $post_push_webhook_data_builder,
        PostMergeRequestWebhookDataBuilder $post_merge_request_webhook_data_builder,
        TagPushWebhookDataBuilder $tag_push_webhook_data_builder,
        LoggerInterface $logger,
    ) {
        $this->post_push_webhook_data_builder          = $post_push_webhook_data_builder;
        $this->post_merge_request_webhook_data_builder = $post_merge_request_webhook_data_builder;
        $this->tag_push_webhook_data_builder           = $tag_push_webhook_data_builder;
        $this->logger                                  = $logger;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     * @throws EmptyBranchNameException
     * @throws MissingEventHeaderException
     * @throws InvalidValueFormatException
     */
    public function retrieveWebhookData(ServerRequestInterface $request): WebhookData
    {
        $webhook_type = $this->getWebhookTypeFromRequestHeader($request);

        $webhook_content = json_decode($request->getBody()->getContents(), true);
        $this->checkCommonJsonKeysAreSet($webhook_content);

        if ($this->isPostPushEvent($webhook_type)) {
            $this->logger->info("|_ Webhook of type $webhook_type received.");
            return $this->post_push_webhook_data_builder->build(
                $webhook_type,
                $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
                $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
                $webhook_content
            );
        }

        if ($this->isTagPushRequestEvent($webhook_type)) {
            $this->logger->info("|_ Webhook of type $webhook_type received.");
            return $this->tag_push_webhook_data_builder->build(
                $webhook_type,
                $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
                $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
                $webhook_content
            );
        }

        $this->logger->info("|_ Webhook of type $webhook_type received.");

        return $this->post_merge_request_webhook_data_builder->build(
            $webhook_type,
            $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
            $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
            $webhook_content
        );
    }

    /**
     * @throws MissingEventHeaderException
     * @throws EventNotAllowedException
     */
    private function getWebhookTypeFromRequestHeader(ServerRequestInterface $request): string
    {
        $gitlab_event_header = $request->getHeaderLine(self::EVENT_HEADER);
        if ($gitlab_event_header === '') {
            throw new MissingEventHeaderException();
        }

        $this->checkEvents($gitlab_event_header);

        return $gitlab_event_header;
    }

    /**
     * @throws EventNotAllowedException
     */
    private function checkEvents(string $webhook_type): void
    {
        if ($this->isPostPushEvent($webhook_type)) {
            return;
        }

        if ($this->isPostMergeRequestEvent($webhook_type)) {
            return;
        }

        if ($this->isTagPushRequestEvent($webhook_type)) {
            return;
        }

        throw new EventNotAllowedException($webhook_type);
    }

    private function isPostPushEvent(string $webhook_type): bool
    {
        return $webhook_type === self::PUSH_EVENT;
    }

    private function isPostMergeRequestEvent(string $webhook_type): bool
    {
        return $webhook_type === self::MERGE_REQUEST_EVENT;
    }

    private function isTagPushRequestEvent(string $webhook_type): bool
    {
        return $webhook_type === self::TAG_PUSH_EVENT;
    }

    /**
     * @throws MissingKeyException
     */
    private function checkCommonJsonKeysAreSet(array $webhook_content): void
    {
        if (! isset($webhook_content[self::PROJECT_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . ' > ' . self::PROJECT_ID_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . ' > ' . self::PROJECT_URL_KEY);
        }
    }
}
