<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\Timeline\SearchAbandonEvent;
use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;

final class SearchAbandonEventStub implements SearchAbandonEvent
{
    private function __construct(private ?int $abandon_date_timestamp, private ?int $user_id)
    {
    }

    public static function withAbandonEvent(int $abandon_date_timestamp, int $user_id): self
    {
        return new self($abandon_date_timestamp, $user_id);
    }

    public static function withNoAbandonEvent(): self
    {
        return new self(null, null);
    }

    public function searchAbandonEventForPullRequest(int $pull_request_id): ?array
    {
        if (! $this->user_id || ! $this->abandon_date_timestamp) {
            return null;
        }

        return [
            'type' => TimelineGlobalEvent::ABANDON,
            'user_id' => $this->user_id,
            'post_date' => $this->abandon_date_timestamp,
        ];
    }
}
