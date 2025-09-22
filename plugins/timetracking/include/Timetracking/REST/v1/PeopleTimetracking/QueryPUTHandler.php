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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class QueryPUTHandler
{
    public function __construct(
        private FromPayloadPeriodBuilder $data_checker,
        private FromPayloadUserListBuilder $user_list_builder,
        private PeopleTimetrackingWidgetSaver $people_timetracking_widget_saver,
        private CheckPermission $permission_checker,
    ) {
    }

    /**
     * @return Ok<UserList>|Err<Fault>
     */
    public function handle(int $query_id, QueryPUTRepresentation $representation, \PFUser $user): Ok|Err
    {
        return $this->permission_checker->checkThatCurrentUserCanUpdateTheQuery($query_id, $user)
            ->andThen(fn () => $this->user_list_builder->getUserList($user, $representation->users))
            ->andThen(fn (UserList $users) => $this->save($query_id, $representation, $users));
    }

    /**
     * @return Ok<UserList>|Err<Fault>
     */
    private function save(
        int $query_id,
        QueryPUTRepresentation $representation,
        UserList $users,
    ): Ok|Err {
        return $this->data_checker->getValidatedPeriod($representation)
            ->andThen(fn (Period $period) => $this->people_timetracking_widget_saver->save($query_id, $period, $users))
            ->andThen(fn () => Result::ok($users));
    }
}
