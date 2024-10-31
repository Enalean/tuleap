<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker;

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CrossTrackerReportCreator
{
    public function __construct(private CreateReport $create_report)
    {
    }

    /**
     * @return Ok<int>|Err<Fault>
     */
    public function createReportAndReturnLastId(string $dashboard_type): Ok|Err
    {
        switch ($dashboard_type) {
            case UserDashboardController::DASHBOARD_TYPE:
            case USerDashboardController::LEGACY_DASHBOARD_TYPE:
                $predefined_expert_query = 'SELECT @pretty_title, @submitted_by, @last_update_date, @status FROM @project = MY_PROJECTS() WHERE @status = OPEN() AND @assigned_to = MYSELF() ORDER BY @last_update_date DESC';
                break;
            case ProjectDashboardController::DASHBOARD_TYPE:
            case ProjectDashboardController::LEGACY_DASHBOARD_TYPE:
                $predefined_expert_query = "SELECT @pretty_title, @submitted_by, @last_update_date, @status, @assigned_to FROM @project = 'self' WHERE @status = OPEN() ORDER BY @last_update_date DESC";
                break;
            default:
                return Result::err(
                    Fault::fromMessage('Invalid dashboard type')
                );
        }

        return Result::ok($this->create_report->createReportFromExpertQuery($predefined_expert_query));
    }
}
