<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';


$tour_name = $request->get('tour_name');

$tour_factory = new Tuleap_TourFactory(ProjectManager::instance(), new URL());
$current_tour = $tour_factory->getTour($current_user, $tour_name);

$stats_dao  = new Tuleap_TourUsageStatsDao();
$tour_usage = new Tuleap_TourUsage($stats_dao);
$tour_usage->endTour(
    $current_user,
    $current_tour,
    $request->getValidated('current_step', 'uint')
);

if ($tour_name === Tuleap_Tour_WelcomeTour::TOUR_NAME) {
    $flaming_parrot_burning_parrot_unification_tour = $tour_factory->getTour(
        $current_user,
        Tuleap_Tour_FlamingParrotBurningParrotUnificationTour::TOUR_NAME
    );

    $tour_usage->endTour(
        $current_user,
        $flaming_parrot_burning_parrot_unification_tour,
        0
    );
}
