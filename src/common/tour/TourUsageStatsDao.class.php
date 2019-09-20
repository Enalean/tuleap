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

class Tuleap_TourUsageStatsDao extends DataAccessObject
{

    public function save($user_id, $tour_name, $nb_steps, $current_step, $the_end)
    {
        $user_id      = $this->da->escapeInt($user_id);
        $executed_on  = $this->da->escapeInt($_SERVER['REQUEST_TIME']);
        $tour_name    = $this->da->quoteSmart($tour_name);
        $nb_steps     = $this->da->escapeInt($nb_steps);
        $current_step = $this->da->escapeInt($current_step);
        $the_end      = $this->da->escapeInt($the_end);

        $sql = "INSERT INTO tour_usage_statistics
            (user_id, executed_on, tour_name, nb_steps, current_step, the_end)
            VALUES
            ($user_id, $executed_on, $tour_name, $nb_steps, $current_step, $the_end)";

        $this->update($sql);
    }
}
