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

namespace Tuleap\PullRequest\StateStatus;

use PFUser;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\PullRequest;

/**
 * @psalm-immutable
 */
final class PullRequestMergedEvent implements EventSubjectToNotification
{
    /**
     * @var int
     */
    private $pull_request_id;
    /**
     * @var int
     */
    private $user_id;

    private function __construct(int $pull_request_id, int $user_id)
    {
        $this->pull_request_id = $pull_request_id;
        $this->user_id         = $user_id;
    }

    public static function fromPullRequestAndUserMergingThePullRequest(PullRequest $pull_request, PFUser $user): self
    {
        return new self($pull_request->getId(), (int) $user->getId());
    }

    public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
    {
        if (! isset($payload['user_id'], $payload['pr_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'user_id and/or pr_id not found in the payload');
        }

        return new self($payload['pr_id'], $payload['user_id']);
    }

    public function getUserID(): int
    {
        return $this->user_id;
    }

    public function getPullRequestID(): int
    {
        return $this->pull_request_id;
    }

    public function toWorkerEventPayload(): array
    {
        return [
            'user_id' => $this->user_id,
            'pr_id'   => $this->pull_request_id
        ];
    }
}
