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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final readonly class QueryPUTHandler
{
    public function __construct(
        private FromPayloadPeriodBuilder $data_checker,
        private FromPayloadUserDiffBuilder $user_diff_builder,
        private TimetrackingManagementWidgetSaver $timetracking_management_widget_saver,
        private CheckPermission $permission_checker,
    ) {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function handle(int $widget_id, QueryPUTRepresentation $representation): Ok|Err
    {
        return $this->permission_checker->checkThatCurrentUserCanUpdateTheQuery($widget_id, \UserManager::instance()->getCurrentUser())
            ->andThen(fn () => $this->user_diff_builder->getUserDiff($widget_id, $representation->users))
            ->andThen(function (UserDiff $user_diff) use ($widget_id, $representation) {
                return $this->data_checker->getValidatedPeriod($representation)
                    ->andThen(fn (Period $period) => $this->timetracking_management_widget_saver->save($widget_id, $period, $user_diff));
            });
    }
}
