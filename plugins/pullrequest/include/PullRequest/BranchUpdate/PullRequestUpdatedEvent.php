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

namespace Tuleap\PullRequest\BranchUpdate;

use PFUser;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\PullRequest;

/**
 * @psalm-immutable
 */
final class PullRequestUpdatedEvent implements EventSubjectToNotification
{
    /**
     * @var int
     */
    private $pull_request_id;
    /**
     * @var int
     */
    private $user_id;
    /**
     * @var string
     */
    private $old_src_reference;
    /**
     * @var string
     */
    private $new_src_reference;
    /**
     * @var string
     */
    private $old_dst_reference;
    /**
     * @var string
     */
    private $new_dst_reference;

    private function __construct(
        int $pull_request_id,
        int $user_id,
        string $old_src_reference,
        string $new_src_reference,
        string $old_dst_reference,
        string $new_dst_reference
    ) {
        $this->pull_request_id   = $pull_request_id;
        $this->user_id           = $user_id;
        $this->old_src_reference = $old_src_reference;
        $this->new_src_reference = $new_src_reference;
        $this->old_dst_reference = $old_dst_reference;
        $this->new_dst_reference = $new_dst_reference;
    }

    public static function fromPullRequestUserAndReferences(
        PullRequest $pull_request,
        PFUser $user,
        string $old_src_reference,
        string $new_src_reference,
        string $old_dst_reference,
        string $new_dst_reference
    ): self {
        return new self(
            $pull_request->getId(),
            (int) $user->getId(),
            $old_src_reference,
            $new_src_reference,
            $old_dst_reference,
            $new_dst_reference
        );
    }

    public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
    {
        if (! isset($payload['user_id'], $payload['pr_id'], $payload['old_src'], $payload['new_src'], $payload['old_dst'], $payload['new_dst'])) {
            throw new InvalidWorkerEventPayloadException(
                self::class,
                'user_id, pr_id, old_src, new_src, old_dst or new_dst is missing from the payload not found in the payload'
            );
        }
        return new self(
            $payload['pr_id'],
            $payload['user_id'],
            $payload['old_src'],
            $payload['new_src'],
            $payload['old_dst'],
            $payload['new_dst']
        );
    }

    public function toWorkerEventPayload(): array
    {
        return [
            'user_id' => $this->user_id,
            'pr_id'   => $this->pull_request_id,
            'old_src' => $this->old_src_reference,
            'new_src' => $this->new_src_reference,
            'old_dst' => $this->old_dst_reference,
            'new_dst' => $this->new_dst_reference,
        ];
    }

    public function getUserID(): int
    {
        return $this->user_id;
    }

    public function getPullRequestID(): int
    {
        return $this->pull_request_id;
    }

    public function getOldSourceReference(): string
    {
        return $this->old_src_reference;
    }

    public function getOldDestinationReference(): string
    {
        return $this->old_dst_reference;
    }

    public function getNewSourceReference(): string
    {
        return $this->new_src_reference;
    }

    public function getNewDestinationReference(): string
    {
        return $this->new_dst_reference;
    }
}
