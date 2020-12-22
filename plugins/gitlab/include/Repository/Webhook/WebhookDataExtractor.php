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
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;

class WebhookDataExtractor
{
    private const EVENT_NAME_KEY     = 'event_name';
    private const PROJECT_KEY        = 'project';
    private const PROJECT_ID_KEY     = 'id';
    private const PROJECT_URL_KEY    = 'web_url';
    private const PUSH_EVENT         = 'push';
    private const COMMITS_BRANCH_KEY = 'ref';

    /**
     * @var PostPushCommitWebhookDataExtractor
     */
    private $post_push_commit_webhook_data_extractor;

    public function __construct(PostPushCommitWebhookDataExtractor $post_push_commit_webhook_data_extractor)
    {
        $this->post_push_commit_webhook_data_extractor = $post_push_commit_webhook_data_extractor;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     * @throws EmptyBranchNameException
     */
    public function retrieveWebhookData(ServerRequestInterface $request): WebhookData
    {
        $webhook_content = json_decode($request->getBody()->getContents(), true);
        $this->checkCommonJsonKeysAreSet($webhook_content);

        return new PostPushWebhookData(
            $webhook_content[self::EVENT_NAME_KEY],
            $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
            $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY],
            $this->post_push_commit_webhook_data_extractor->retrieveWebhookCommitsData(
                $webhook_content,
                WebhookDataBranchNameExtractor::extractBranchName($webhook_content[self::COMMITS_BRANCH_KEY])
            )
        );
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     */
    private function checkCommonJsonKeysAreSet(array $webhook_content): void
    {
        if (! isset($webhook_content[self::EVENT_NAME_KEY])) {
            throw new MissingKeyException(self::EVENT_NAME_KEY);
        }

        if ($webhook_content[self::EVENT_NAME_KEY] !== self::PUSH_EVENT) {
            throw new EventNotAllowedException($webhook_content[self::EVENT_NAME_KEY]);
        }

        if (! isset($webhook_content[self::PROJECT_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_ID_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_URL_KEY);
        }
        if (! isset($webhook_content[self::COMMITS_BRANCH_KEY])) {
            throw new MissingKeyException(self::COMMITS_BRANCH_KEY);
        }
    }
}
