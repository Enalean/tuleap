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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use Tuleap\Gitlab\Repository\Webhook\InvalidValueFormatException;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class TagPushWebhookDataBuilder
{
    private const string REF_KEY    = 'ref';
    private const string BEFORE_KEY = 'before';
    private const string AFTER_KEY  = 'after';

    /**
     * @throws MissingKeyException
     * @throws InvalidValueFormatException
     */
    public function build(string $event_name, int $project_id, string $project_url, array $tag_push_content): TagPushWebhookData
    {
        if (! isset($tag_push_content[self::REF_KEY])) {
            throw new MissingKeyException(self::REF_KEY);
        }

        if (! isset($tag_push_content[self::BEFORE_KEY])) {
            throw new MissingKeyException(self::BEFORE_KEY);
        }

        if (! isset($tag_push_content[self::AFTER_KEY])) {
            throw new MissingKeyException(self::AFTER_KEY);
        }

        if (! is_string($tag_push_content[self::REF_KEY])) {
            throw new InvalidValueFormatException(self::REF_KEY, 'string');
        }

        if (! is_string($tag_push_content[self::BEFORE_KEY])) {
            throw new InvalidValueFormatException(self::BEFORE_KEY, 'string');
        }

        if (! is_string($tag_push_content[self::AFTER_KEY])) {
            throw new InvalidValueFormatException(self::AFTER_KEY, 'string');
        }

        return new TagPushWebhookData(
            $event_name,
            $project_id,
            $project_url,
            $tag_push_content[self::REF_KEY],
            $tag_push_content[self::BEFORE_KEY],
            $tag_push_content[self::AFTER_KEY],
        );
    }
}
