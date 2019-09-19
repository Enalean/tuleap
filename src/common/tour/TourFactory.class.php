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

class Tuleap_TourFactory
{

    /** @var ProjectManager */
    private $project_manager;

    /** @var URL */
    private $url_processor;

    public function __construct(ProjectManager $project_manager, URL $url_processor)
    {
        $this->project_manager = $project_manager;
        $this->url_processor   = $url_processor;
    }

    /**
     * Instantiate a Tuleap_Tour by its name
     *
     * @throws Tuleap_UnknownTourException
     * @throws Tuleap_InvalidTourException
     *
     * @return Tuleap_Tour
     */
    public function getTour(PFUser $user, $tour_name)
    {
        switch ($tour_name) {
            case Tuleap_Tour_WelcomeTour::TOUR_NAME:
                $tour = new Tuleap_Tour_WelcomeTour($user);
                break;
            case Tuleap_Tour_FlamingParrotBurningParrotUnificationTour::TOUR_NAME:
                $tour = new Tuleap_Tour_FlamingParrotBurningParrotUnificationTour();
                break;
            default:
                $tour = $this->getCustomTour($user, $tour_name);
        }

        return $tour;
    }

    private function getCustomTour($user, $tour_name)
    {
        $custom_tours_factory = new Tuleap_CustomToursFactory($this->project_manager, $this->url_processor);
        return $custom_tours_factory->getTour($user, $tour_name);
    }

    /**
     * @return Tuleap_Tour[]
     */
    public function getToursForPage(PFUser $user, $current_location)
    {
        $custom_tours_factory = new Tuleap_CustomToursFactory($this->project_manager, $this->url_processor);
        return $custom_tours_factory->getToursForPage($user, $current_location);
    }
}
