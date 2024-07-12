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

use DateTimeImmutable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class TimetrackingManagementWidgetSaver
{
    private SaveQuery $dao;

    public function __construct(SaveQuery $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function saveConfiguration(int $widget_id, DatetimeImmutable $start_date, DatetimeImmutable $end_date): Ok|Err
    {
        $this->dao->saveQueryWithDates($widget_id, $start_date, $end_date);

        return Result::ok(true);
    }
}
