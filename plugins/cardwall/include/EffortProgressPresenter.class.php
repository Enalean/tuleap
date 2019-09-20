<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

interface Cardwall_EffortProgressPresenter
{
    public function milestone_capacity();

    public function milestone_initial_effort_value();

    public function milestone_initial_effort();

    public function milestone_has_initial_effort();

    public function milestone_points_to_go();

    public function milestone_remaining_effort();

    public function initial_effort_completion();

    public function milestone_count_style();

    public function count_style_helper();
}
