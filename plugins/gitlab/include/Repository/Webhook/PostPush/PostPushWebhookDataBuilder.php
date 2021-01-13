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

class PostPushWebhookDataBuilder
{
    /**
     * @var PostPushCommitWebhookDataExtractor
     */
    private $commits_extractor;

    public function __construct(PostPushCommitWebhookDataExtractor $commits_extractor)
    {
        $this->commits_extractor = $commits_extractor;
    }

    public function build(string $event_name, int $project_id, string $project_url, array $webhook_content): PostPushWebhookData
    {
        return new PostPushWebhookData(
            $event_name,
            $project_id,
            $project_url,
            $this->commits_extractor->retrieveWebhookCommitsData($webhook_content)
        );
    }
}
