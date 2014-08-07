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

class Tuleap_TourFactory {

    const CUSTOM_TOURS_LIST_FILE = 'tour.json';

    /**
     * Instantiate a Tuleap_Tour by its name
     *
     * @throws Exception when tour is unknown
     *
     * @param type $tour_name
     *
     * @return Tuleap_Tour
     */
    public function getTour(PFUser $user, $tour_name) {
        switch ($tour_name) {
            case Tuleap_Tour_WelcomeTour::TOUR_NAME:
                $tour = new Tuleap_Tour_WelcomeTour($user);
                break;
            default:
                throw new Exception("Unknown tour '$tour_name'");
        }

        return $tour;
    }

    public function getCustomToursForPage(PFUser $user, $request_uri) {
        $tour_folder   = $this->getTourFolder($user);
        $enabled_tours = $this->getEnabledTours($tour_folder);
        if (! $enabled_tours) {
            return array();
        }
        $custom_tours = array();
        foreach ($enabled_tours as $enabled_tour) {
            $file_name = $tour_folder.$enabled_tour['tour_name'].'.json';
            if (! file_exists($file_name)) {
                continue;
            }

            $tour = json_decode(file_get_contents($file_name), true);
            if ($enabled_tour['url'] == $request_uri) {
                $custom_tours[$enabled_tour['tour_name']] = $tour;
            }
        }

        return $custom_tours;
    }

    private function getEnabledTours($tour_folder) {
        $config_file = $tour_folder.self::CUSTOM_TOURS_LIST_FILE;
        if (! is_dir($tour_folder) || ! file_exists($config_file)) {
            return;
        }

        $enabled_tours = json_decode(file_get_contents($config_file), true);
        if (! is_array($enabled_tours)) {
            return;
        }

        return $enabled_tours;
    }

    private function getTourFolder(PFUser $user) {
        $user_lang = $user->getLocale();
        return Config::get('sys_custom_incdir').'/'.$user_lang.'/tour/';
    }
}