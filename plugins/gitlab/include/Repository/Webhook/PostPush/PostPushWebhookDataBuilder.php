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

use Tuleap\Gitlab\Repository\Webhook\InvalidValueFormatException;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class PostPushWebhookDataBuilder
{
    private const string REFERENCE_KEY    = 'ref';
    private const string CHECKOUT_SHA_KEY = 'checkout_sha';

    /**
     * @var PostPushCommitWebhookDataExtractor
     */
    private $commits_extractor;

    public function __construct(PostPushCommitWebhookDataExtractor $commits_extractor)
    {
        $this->commits_extractor = $commits_extractor;
    }

    public function build(
        string $event_name,
        int $project_id,
        string $project_url,
        array $webhook_content,
    ): PostPushWebhookData {
        if (! array_key_exists(self::REFERENCE_KEY, $webhook_content)) {
            throw new MissingKeyException(self::REFERENCE_KEY);
        }

        if (! array_key_exists(self::CHECKOUT_SHA_KEY, $webhook_content)) {
            throw new MissingKeyException(self::CHECKOUT_SHA_KEY);
        }

        if (! is_string($webhook_content[self::REFERENCE_KEY])) {
            throw new InvalidValueFormatException(self::REFERENCE_KEY, 'string');
        }

        if (
            ! is_string($webhook_content[self::CHECKOUT_SHA_KEY]) &&
            ! is_null($webhook_content[self::CHECKOUT_SHA_KEY])
        ) {
            throw new InvalidValueFormatException(self::CHECKOUT_SHA_KEY, 'string|null');
        }

        return new PostPushWebhookData(
            $event_name,
            $project_id,
            $project_url,
            $webhook_content[self::CHECKOUT_SHA_KEY],
            $webhook_content[self::REFERENCE_KEY],
            $this->commits_extractor->retrieveWebhookCommitsData($webhook_content)
        );
    }
}
