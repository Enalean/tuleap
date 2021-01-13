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

use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;
use Psr\Log\LoggerInterface;

class PostMergeRequestWebhookDataBuilder
{
    private const OBJECT_ATTRIBUTES_KEY         = "object_attributes";
    private const MERGE_REQUEST_ID_KEY          = "id";
    private const MERGE_REQUEST_TITLE_KEY       = "title";
    private const MERGE_REQUEST_DESCRIPTION_KEY = "description";
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function build(string $event_name, int $project_id, string $project_url, array $webhook_content): PostMergeRequestWebhookData
    {
        $this->checkNoMissingKeyInMergeRequestData($webhook_content);

        $merge_request_id = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_ID_KEY];
        $title            = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_TITLE_KEY];
        $description      = $webhook_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_DESCRIPTION_KEY];

        $this->logger->debug("Webhook merge request with id $merge_request_id retrieved.");
        $this->logger->debug("|_ Its title is: $title");
        $this->logger->debug("|_ Its description is: $description");

        return new PostMergeRequestWebhookData(
            $event_name,
            $project_id,
            $project_url,
            $merge_request_id,
            $title,
            $description
        );
    }

    /**
     * @throws MissingKeyException
     */
    private function checkNoMissingKeyInMergeRequestData(array $merge_request_content): void
    {
        if (! isset($merge_request_content[self::OBJECT_ATTRIBUTES_KEY])) {
            throw new MissingKeyException(self::OBJECT_ATTRIBUTES_KEY);
        }

        if (! isset($merge_request_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_ID_KEY])) {
            throw new MissingKeyException(self::MERGE_REQUEST_ID_KEY . ' in ' . self::OBJECT_ATTRIBUTES_KEY);
        }

        if (! isset($merge_request_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_TITLE_KEY])) {
            throw new MissingKeyException(self::MERGE_REQUEST_TITLE_KEY . ' in ' . self::OBJECT_ATTRIBUTES_KEY);
        }

        if (! isset($merge_request_content[self::OBJECT_ATTRIBUTES_KEY][self::MERGE_REQUEST_DESCRIPTION_KEY])) {
            throw new MissingKeyException(self::MERGE_REQUEST_DESCRIPTION_KEY . ' in ' . self::OBJECT_ATTRIBUTES_KEY);
        }
    }
}
