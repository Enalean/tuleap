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

class WebhookDataExtractor
{
    private const EVENT_NAME_KEY      = 'event_name';
    private const EVENT_TYPE_KEY      = 'event_type';
    private const PROJECT_KEY         = 'project';
    private const PROJECT_ID_KEY      = 'id';
    private const PROJECT_URL_KEY     = 'web_url';
    private const PUSH_EVENT          = 'push';
    private const MERGE_REQUEST_EVENT = 'merge_request';

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

    public function __construct(
        PostPushWebhookDataBuilder $post_push_webhook_data_builder,
        PostMergeRequestWebhookDataBuilder $post_merge_request_webhook_data_builder,
        LoggerInterface $logger
    ) {
        $this->post_push_webhook_data_builder          = $post_push_webhook_data_builder;
        $this->post_merge_request_webhook_data_builder = $post_merge_request_webhook_data_builder;
        $this->logger                                  = $logger;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     * @throws EmptyBranchNameException
     * @throws MissingEventKeysException
     */
    public function retrieveWebhookData(ServerRequestInterface $request): WebhookData
    {
        $webhook_content = json_decode($request->getBody()->getContents(), true);
        $this->checkCommonJsonKeysAreSet($webhook_content);

        if ($this->isPostPushEvent($webhook_content)) {
            $this->logger->info("|_ Webhook of type {$webhook_content[self::EVENT_NAME_KEY]} received.");
            return $this->post_push_webhook_data_builder->build(
                $webhook_content[self::EVENT_NAME_KEY],
                $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
                $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
                $webhook_content
            );
        }

        $this->logger->info("|_ Webhook of type {$webhook_content[self::EVENT_TYPE_KEY]} received.");

        return $this->post_merge_request_webhook_data_builder->build(
            $webhook_content[self::EVENT_TYPE_KEY],
            $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
            $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
            $webhook_content
        );
    }

    private function checkEvents(array $webhook_content): void
    {
        if ($this->isPostPushEvent($webhook_content)) {
            return;
        }

        if ($this->isPostMergeRequestEvent($webhook_content)) {
            return;
        }

        if (! isset($webhook_content[self::EVENT_NAME_KEY]) && ! isset($webhook_content[self::EVENT_TYPE_KEY])) {
            throw new MissingEventKeysException([self::EVENT_NAME_KEY, self::EVENT_TYPE_KEY]);
        }

        if (isset($webhook_content[self::EVENT_NAME_KEY])) {
            throw new EventNotAllowedException($webhook_content[self::EVENT_NAME_KEY]);
        }

        if (isset($webhook_content[self::EVENT_TYPE_KEY])) {
            throw new EventNotAllowedException($webhook_content[self::EVENT_TYPE_KEY]);
        }
    }

    private function isPostPushEvent(array $webhook_content): bool
    {
        return isset($webhook_content[self::EVENT_NAME_KEY]) && $webhook_content[self::EVENT_NAME_KEY] === self::PUSH_EVENT;
    }

    private function isPostMergeRequestEvent(array $webhook_content): bool
    {
        return isset($webhook_content[self::EVENT_TYPE_KEY]) && $webhook_content[self::EVENT_TYPE_KEY] === self::MERGE_REQUEST_EVENT;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     */
    private function checkCommonJsonKeysAreSet(array $webhook_content): void
    {
        $this->checkEvents($webhook_content);

        if (! isset($webhook_content[self::PROJECT_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_ID_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_URL_KEY);
        }
    }
}
