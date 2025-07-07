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

/**
 * @psalm-immutable
 */
final class QueryPUTRepresentation
{
    /**
     * @var string | null $start_date {@from body} {@required false}
     */
    public ?string $start_date = null;

    /**
     * @var string | null $end_date {@from body} {@required false}
     */
    public ?string $end_date = null;

    /**
     * @var string | null $predefined_time_period {@from body} {@required false} {@choice today,yesterday,current_week,last_7_days,last_week,last_month}
     */
    public ?string $predefined_time_period = null;

    /**
     * @var array $users {@type \Tuleap\Timetracking\REST\v1\TimetrackingManagement\QueryUserRepresentation} {@from body} {@required false}
     * @psalm-param QueryUserRepresentation[] $users
     */
    public array $users = [];

    public function __construct(?string $start_date, ?string $end_date, ?string $predefined_time_period, array $users)
    {
        $this->start_date             = $start_date;
        $this->end_date               = $end_date;
        $this->predefined_time_period = $predefined_time_period;
        $this->users                  = $users;
    }
}
