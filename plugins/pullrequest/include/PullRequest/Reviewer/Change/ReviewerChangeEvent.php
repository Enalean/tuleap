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

namespace Tuleap\PullRequest\Reviewer\Change;

use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;

/**
 * @psalm-immutable
 */
final class ReviewerChangeEvent implements EventSubjectToNotification
{
    /**
     * @var int
     */
    private $change_id;

    private function __construct(int $change_id)
    {
        $this->change_id = $change_id;
    }

    public static function fromID(int $change_id): self
    {
        return new self($change_id);
    }

    public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
    {
        if (! isset($payload['change_id'])) {
            throw new InvalidWorkerEventPayloadException(self::class, 'change_id not found in the payload');
        }

        return new self($payload['change_id']);
    }

    public function getChangeID(): int
    {
        return $this->change_id;
    }

    public function toWorkerEventPayload(): array
    {
        return [
            'change_id' => $this->change_id
        ];
    }
}
