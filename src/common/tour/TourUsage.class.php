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

class Tuleap_TourUsage
{

    /**
     * @var Tuleap_TourUsageStatsDao
     */
    private $stats_dao;

    public function __construct(Tuleap_TourUsageStatsDao $stats_dao)
    {
        $this->stats_dao = $stats_dao;
    }

    public function endTour(PFUser $user, Tuleap_Tour $tour, $current_step)
    {
        $user->setPreference($tour->name, true);
        $this->registerCurrentStep($user, $tour, $current_step, true);
    }

    public function stepShown(PFUser $user, Tuleap_Tour $tour, $current_step)
    {
        $this->registerCurrentStep($user, $tour, $current_step, false);
    }

    private function registerCurrentStep(PFUser $user, Tuleap_Tour $tour, $current_step, $the_end)
    {
        $this->stats_dao->save(
            $user->getId(),
            $tour->name,
            count($tour->steps),
            $current_step,
            $the_end
        );
    }
}
