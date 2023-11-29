<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Comment\Notification;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;

/**
 * @psalm-immutable
 */
final class UpdatedCommentEvent implements EventSubjectToNotification
{
    private function __construct(public readonly int $comment_id)
    {
    }

    public static function fromUpdatedComment(Comment $updated_comment): self
    {
        return new self($updated_comment->getId());
    }

    public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
    {
        if (! isset($payload['comment_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'comment_id not found in the payload');
        }

        return new self($payload['comment_id']);
    }

    public function toWorkerEventPayload(): array
    {
        return [
            'comment_id' => $this->comment_id,
        ];
    }
}
