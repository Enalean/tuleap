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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class PostPushCommitWebhookDataExtractor
{
    private const COMMIT_KEY         = 'commits';
    private const COMMIT_SHA1_KEY    = 'id';
    private const COMMIT_MESSAGE_KEY = 'message';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return PostPushCommitWebhookData[]
     *
     * @throws MissingKeyException
     */
    public function retrieveWebhookCommitsData(array $webhook_content): array
    {
        if (! isset($webhook_content[self::COMMIT_KEY])) {
            throw new MissingKeyException(self::COMMIT_KEY);
        }

        $commits = [];
        foreach ($webhook_content[self::COMMIT_KEY] as $commit_content) {
            $commits[] = $this->retrieveCommitData($commit_content);
        }

        return $commits;
    }

    /**
     * @throws MissingKeyException
     */
    private function retrieveCommitData(array $commit_content): PostPushCommitWebhookData
    {
        if (! isset($commit_content[self::COMMIT_SHA1_KEY])) {
            throw new MissingKeyException(self::COMMIT_SHA1_KEY);
        }

        if (! isset($commit_content[self::COMMIT_MESSAGE_KEY])) {
            throw new MissingKeyException(self::COMMIT_MESSAGE_KEY);
        }

        $sha1    = $commit_content[self::COMMIT_SHA1_KEY];
        $message = $commit_content[self::COMMIT_MESSAGE_KEY];

        $this->logger->debug("Webhook commit with sha1 $sha1 retrieved.");
        $this->logger->debug("Its commit message is: $message");

        return new PostPushCommitWebhookData(
            $sha1,
            $message
        );
    }
}
