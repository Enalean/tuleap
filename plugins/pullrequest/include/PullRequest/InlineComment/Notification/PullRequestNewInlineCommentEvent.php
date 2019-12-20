<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\InlineComment\Notification;

use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;

/**
 * @psalm-immutable
 */
final class PullRequestNewInlineCommentEvent implements EventSubjectToNotification
{
    /**
     * @var int
     */
    private $inline_comment_id;

    private function __construct(int $inline_comment_id)
    {
        $this->inline_comment_id = $inline_comment_id;
    }

    public static function fromInlineCommentID(int $inline_comment_id): self
    {
        return new self($inline_comment_id);
    }

    public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
    {
        if (! isset($payload['inline_comment_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'inline_comment_id not found in the payload');
        }

        return new self($payload['inline_comment_id']);
    }

    public function getInlineCommentID(): int
    {
        return $this->inline_comment_id;
    }

    public function toWorkerEventPayload(): array
    {
        return [
            'inline_comment_id' => $this->inline_comment_id
        ];
    }
}
