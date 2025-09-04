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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\Widget\Management\GroupTimeSpentInArtifactByUser;
use Tuleap\Timetracking\Widget\Management\ListOfTimeSpentInArtifactFilter;
use Tuleap\Timetracking\Widget\Management\RetrieveListOfTimeSpentInArtifact;
use Tuleap\Timetracking\Widget\Management\RetrieveUserTimesTimeframe;
use Tuleap\Timetracking\Widget\Management\TimeSpentInArtifact;
use Tuleap\Timetracking\Widget\Management\UserTimes;
use Tuleap\Timetracking\Widget\Management\UserTimesTimeframe;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final readonly class TimesGETHandler
{
    public function __construct(
        private RetrieveListOfTimeSpentInArtifact $time_retriever,
        private ListOfTimeSpentInArtifactFilter $filter,
        private GroupTimeSpentInArtifactByUser $grouper,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private RetrieveUserTimesTimeframe $timeframe_retriever,
        private SearchUsersByWidgetId $dao_user,
        private RetrieveUserById $retrieve_user,
    ) {
    }

    /**
     * @return Ok<PaginatedListOfUserTimesRepresentation>|Err<Fault>
     */
    public function handle(int $query_id, int $limit, int $offset, \PFUser $manager): Ok|Err
    {
        return $this->timeframe_retriever->getTimeframe($query_id, $manager)
            ->andThen(
                function (UserTimesTimeframe $timeframe) use ($query_id, $limit, $offset, $manager) {
                    $user_ids   = $this->dao_user->searchUsersByQueryId($query_id);
                    $total_size = count($user_ids);
                    $user_ids   = array_slice($user_ids, $offset, $limit);

                    $times = $this->getUserTimes($timeframe, ...$user_ids);

                    return Result::ok(new PaginatedListOfUserTimesRepresentation(
                        $this->transformListOfTimeSpentInArtifactIntoListOfUserTimesRepresentation($times, $manager),
                        $total_size,
                    ));
                }
            );
    }

    /**
     * @return list<TimeSpentInArtifact>
     */
    private function getUserTimes(UserTimesTimeframe $timeframe, int ...$user_ids): array
    {
        $times = [];
        foreach ($user_ids as $user_id) {
            $user = $this->retrieve_user->getUserById($user_id);
            if ($user === null) {
                continue;
            }

            if (! $user->isAlive()) {
                continue;
            }

            $times[] = $this->time_retriever->getUserTimesPerArtifact($user, $timeframe->start, $timeframe->end);
        }

        return array_merge(...$times);
    }

    /**
     * @param list<TimeSpentInArtifact> $times
     *
     * @return list<UserTimesRepresentation>
     */
    private function transformListOfTimeSpentInArtifactIntoListOfUserTimesRepresentation(array $times, \PFUser $manager): array
    {
        return array_map(
            fn (UserTimes $user_times) => UserTimesRepresentation::fromUserTimes($user_times, $this->provide_user_avatar_url),
            $this->grouper->groupByUser(
                $this->filter->filterForManager($times, $manager)
            ),
        );
    }
}
