<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\User\History;

use Event;
use Tuleap\Dashboard\Project\DeleteVisitByUserId;

class HistoryCleaner
{
    public function __construct(
        private readonly \EventManager $event_manager,
        private readonly DeleteVisitByUserId $recently_visited_project_dashboard_dao,
    ) {
    }

    public function clearHistory(\PFUser $user): void
    {
        $this->recently_visited_project_dashboard_dao->deleteVisitByUserId((int) $user->getId());
        $this->event_manager->processEvent(
            Event::USER_HISTORY_CLEAR,
            [
                'user' => $user,
            ]
        );
    }
}
