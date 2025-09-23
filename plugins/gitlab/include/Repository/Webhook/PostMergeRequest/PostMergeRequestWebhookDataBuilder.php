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
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class PostMergeRequestWebhookDataBuilder
{
    private const string OBJECT_ATTRIBUTES_KEY         = 'object_attributes';
    private const string MERGE_REQUEST_ID_KEY          = 'iid';
    private const string MERGE_REQUEST_TITLE_KEY       = 'title';
    private const string MERGE_REQUEST_DESCRIPTION_KEY = 'description';
    private const string MERGE_REQUEST_STATE_KEY       = 'state';
    private const string MERGE_REQUEST_CREATED_AT_KEY  = 'created_at';
    private const string MERGE_REQUEST_AUTHOR_ID_KEY   = 'author_id';
    private const string MERGE_REQUEST_SOURCE_BRANCH   = 'source_branch';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function build(
        string $event_name,
        int $project_id,
        string $project_url,
        array $webhook_content,
    ): PostMergeRequestWebhookData {
        $this->checkNoMissingKeyInMergeRequestData($webhook_content);

        $merge_request_id = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_ID_KEY];
        $title            = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_TITLE_KEY];
        $description      = (string) $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_DESCRIPTION_KEY];
        $state            = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_STATE_KEY];
        $author_id        = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_AUTHOR_ID_KEY];
        $source_branch    = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_SOURCE_BRANCH];

        $created_at = new DateTimeImmutable(
            $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_CREATED_AT_KEY]
        );

        $this->logger->debug(
            sprintf(
                "Webhook merge request with id %d retrieved.\nTitle: %s\nSource branch: %s\nDescription: %s\n\n",
                $merge_request_id,
                $title,
                $source_branch,
                $description
            )
        );

        return new PostMergeRequestWebhookData(
            $event_name,
            $project_id,
            $project_url,
            $merge_request_id,
            $title,
            $description,
            $state,
            $created_at,
            $author_id,
            $source_branch
        );
    }

    /**
     * @throws MissingKeyException
     */
    private function checkNoMissingKeyInMergeRequestData(array $merge_request_content): void
    {
        if (! array_key_exists(self::OBJECT_ATTRIBUTES_KEY, $merge_request_content)) {
            throw new MissingKeyException(self::OBJECT_ATTRIBUTES_KEY);
        }

        $required_keys = [
            self::MERGE_REQUEST_ID_KEY,
            self::MERGE_REQUEST_TITLE_KEY,
            self::MERGE_REQUEST_DESCRIPTION_KEY,
            self::MERGE_REQUEST_STATE_KEY,
            self::MERGE_REQUEST_CREATED_AT_KEY,
            self::MERGE_REQUEST_AUTHOR_ID_KEY,
            self::MERGE_REQUEST_SOURCE_BRANCH,
        ];

        foreach ($required_keys as $required_key) {
            if (! array_key_exists($required_key, $merge_request_content[self::OBJECT_ATTRIBUTES_KEY])) {
                throw new MissingKeyException($required_key . ' in ' . self::OBJECT_ATTRIBUTES_KEY);
            }
        }
    }
}
