<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

/**
 * @psalm-immutable
 */
final readonly class PullRequestDescriptionUpdatedEvent implements EventSubjectToNotification
{
    private function __construct(private int $pull_request_id, private int $user_id)
    {
    }

    public static function fromPullRequestIdAndUserId(int $pull_request_id, int $user_id): self
    {
        return new self($pull_request_id, $user_id);
    }

    #[\Override]
    public static function fromWorkerEventPayload(array $payload): self
    {
        if (! isset($payload['pull_request_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'pull_request_id not found in the payload');
        }
        if (! isset($payload['user_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'user_id not found in the payload');
        }

        return new self($payload['pull_request_id'], $payload['user_id']);
    }

    public function getPullRequestId(): int
    {
        return $this->pull_request_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    #[\Override]
    public function toWorkerEventPayload(): array
    {
        return [
            'pull_request_id' => $this->pull_request_id,
            'user_id'         => $this->user_id,
        ];
    }
}
