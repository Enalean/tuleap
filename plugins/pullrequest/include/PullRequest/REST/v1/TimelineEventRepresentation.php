<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final class TimelineEventRepresentation
{
    public readonly MinimalUserRepresentation $user;
    /**
     * @var string $post_date {@type date}
     */
    public readonly string $post_date;
    public readonly string $event_type;
    public string $type = 'timeline-event';
    public readonly int $parent_id;

    public function __construct(
        MinimalUserRepresentation $user,
        \DateTimeImmutable $post_date,
        int $event_type,
        int $parent_id,
    ) {
        $this->user       = $user;
        $this->post_date  = JsonCast::fromNotNullDateTimeToDate($post_date);
        $this->event_type = PullRequestStatusTypeConverter::fromIntStatusToStringStatus($event_type);
        $this->parent_id  = $parent_id;
    }
}
