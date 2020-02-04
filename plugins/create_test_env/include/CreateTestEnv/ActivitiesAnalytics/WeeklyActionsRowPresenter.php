<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\CreateTestEnv\ActivitiesAnalytics;

/**
 * @psalm-immutable
 */
final class WeeklyActionsRowPresenter
{
    public $week;
    public $total_users;
    public $total_actions;
    public $less_than_10;
    public $between_11_and_50;
    public $between_51_and_100;
    public $more_than_100;

    public function __construct(string $week, int $nb_actions, array $quartiles)
    {
        $this->week               = $week;
        $this->total_users        = $quartiles[10] + $quartiles[50] + $quartiles[100] + $quartiles[101];
        $this->total_actions      = $nb_actions;
        $this->less_than_10       = $quartiles[10];
        $this->between_11_and_50  = $quartiles[50];
        $this->between_51_and_100 = $quartiles[100];
        $this->more_than_100      = $quartiles[101];
    }
}
