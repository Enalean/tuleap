<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use Tuleap\Timetracking\Widget\People\TimeSpentInProject;
use Tuleap\Timetracking\Widget\People\UserTimes;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final class UserTimesRepresentation
{
    /**
     * @param TimeSpentInProjectRepresentation[] $times
     */
    private function __construct(public MinimalUserRepresentation $user, public array $times)
    {
    }

    public static function fromUserTimes(UserTimes $user_times, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            MinimalUserRepresentation::build($user_times->user, $provide_user_avatar_url),
            array_map(
                static fn (TimeSpentInProject $time) => TimeSpentInProjectRepresentation::fromTimeSpentInProject($time),
                $user_times->times,
            ),
        );
    }
}
