<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use Tuleap\REST\JsonCast;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SearchQueryByWidgetId;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SearchUsersByWidgetId;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final readonly class TimetrackingManagementWidgetConfig
{
    /**
     * @param MinimalUserRepresentation[] $users
     */
    private function __construct(
        public int $id,
        public ?string $start_date,
        public ?string $end_date,
        public ?string $predefined_time,
        public array $users,
    ) {
    }

    public static function fromId(
        int $id,
        SearchQueryByWidgetId $dao_query,
        SearchUsersByWidgetId $dao_user,
        RetrieveUserById $retrieve_user,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        $query = $dao_query->searchQueryById($id);
        if ($query === null) {
            throw new \RuntimeException("Unable to find configuration for widget id $id");
        }

        $user_ids = $dao_user->searchUsersByQueryId($id);
        $users    = [];
        foreach ($user_ids as $user_id) {
            $user = $retrieve_user->getUserById($user_id);
            if ($user === null) {
                continue;
            }

            $users[] = MinimalUserRepresentation::build($user, $provide_user_avatar_url);
        }

        return new self(
            $query['id'],
            JsonCast::toDate($query['start_date']),
            JsonCast::toDate($query['end_date']),
            $query['predefined_time_period'],
            $users
        );
    }
}
