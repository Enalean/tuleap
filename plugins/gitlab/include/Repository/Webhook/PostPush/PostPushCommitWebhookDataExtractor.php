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

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataBranchNameExtractor;

class PostPushCommitWebhookDataExtractor
{
    private const string COMMIT_KEY              = 'commits';
    private const string COMMIT_SHA1_KEY         = 'id';
    private const string COMMIT_TITLE_KEY        = 'title';
    private const string COMMIT_MESSAGE_KEY      = 'message';
    private const string COMMIT_DATE_KEY         = 'timestamp';
    private const string COMMIT_AUTHOR_KEY       = 'author';
    private const string COMMIT_AUTHOR_EMAIL_KEY = 'email';
    private const string COMMIT_AUTHOR_NAME_KEY  = 'name';
    private const string COMMITS_BRANCH_KEY      = 'ref';

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
        if (! isset($webhook_content[self::COMMITS_BRANCH_KEY])) {
            throw new MissingKeyException(self::COMMITS_BRANCH_KEY);
        }

        if (! isset($webhook_content[self::COMMIT_KEY])) {
            throw new MissingKeyException(self::COMMIT_KEY);
        }

        $branch_name = WebhookDataBranchNameExtractor::extractBranchName($webhook_content[self::COMMITS_BRANCH_KEY]);
        $commits     = [];

        foreach ($webhook_content[self::COMMIT_KEY] as $commit_content) {
            $commits[] = $this->retrieveCommitData($commit_content, $branch_name);
        }

        return $commits;
    }

    /**
     * @throws MissingKeyException
     */
    private function retrieveCommitData(array $commit_content, string $branch_name): PostPushCommitWebhookData
    {
        $this->checkNoMissingKeyInCommitData($commit_content);

        $sha1         = $commit_content[self::COMMIT_SHA1_KEY];
        $title        = $commit_content[self::COMMIT_TITLE_KEY];
        $message      = $commit_content[self::COMMIT_MESSAGE_KEY];
        $author_email = $commit_content[self::COMMIT_AUTHOR_KEY][self::COMMIT_AUTHOR_EMAIL_KEY];
        $author_name  = $commit_content[self::COMMIT_AUTHOR_KEY][self::COMMIT_AUTHOR_NAME_KEY];
        $commit_date  = (new DateTimeImmutable(
            $commit_content[self::COMMIT_DATE_KEY]
        ))->getTimestamp();

        $this->logger->debug("Webhook commit with sha1 $sha1 retrieved.");
        $this->logger->debug("  |_ It has been created by: $author_name ($author_email)");
        $this->logger->debug("  |_ Its branch is: $branch_name");
        $this->logger->debug("  |_ Its commit message is: $message");

        return new PostPushCommitWebhookData(
            $sha1,
            $title,
            $message,
            $branch_name,
            $commit_date,
            $author_email,
            $author_name
        );
    }

    private function checkNoMissingKeyInCommitData(array $commit_content): void
    {
        if (! isset($commit_content[self::COMMIT_SHA1_KEY])) {
            throw new MissingKeyException(self::COMMIT_SHA1_KEY);
        }

        if (! isset($commit_content[self::COMMIT_TITLE_KEY])) {
            throw new MissingKeyException(self::COMMIT_TITLE_KEY);
        }

        if (! isset($commit_content[self::COMMIT_MESSAGE_KEY])) {
            throw new MissingKeyException(self::COMMIT_MESSAGE_KEY);
        }

        if (! isset($commit_content[self::COMMIT_DATE_KEY])) {
            throw new MissingKeyException(self::COMMIT_DATE_KEY);
        }

        if (! isset($commit_content[self::COMMIT_AUTHOR_KEY])) {
            throw new MissingKeyException(self::COMMIT_AUTHOR_KEY);
        }

        if (! isset($commit_content[self::COMMIT_AUTHOR_KEY][self::COMMIT_AUTHOR_EMAIL_KEY])) {
            throw new MissingKeyException(self::COMMIT_AUTHOR_EMAIL_KEY . ' in ' . self::COMMIT_AUTHOR_KEY);
        }

        if (! isset($commit_content[self::COMMIT_AUTHOR_KEY][self::COMMIT_AUTHOR_NAME_KEY])) {
            throw new MissingKeyException(self::COMMIT_AUTHOR_NAME_KEY . ' in ' . self::COMMIT_AUTHOR_KEY);
        }
    }
}
