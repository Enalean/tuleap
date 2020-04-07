<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

class Tuleap_CustomToursFactory
{

    public const CUSTOM_TOURS_LIST_FILE      = 'tour.json';
    public const PLACEHOLDER_PROJECT_ID      = '{project_id}';
    public const PLACEHOLDER_PROJECT_NAME    = '{project_name}';
    public const PLACEHOLDER_ATTRIBUTE_VALUE = '{attribute_value}';

    /** @var ProjectManager */
    private $project_manager;

    /** @var Url */
    private $url_processor;

    public function __construct(ProjectManager $project_manager, URL $url_processor)
    {
        $this->project_manager = $project_manager;
        $this->url_processor   = $url_processor;
    }

    /**
     * @return Tuleap_Tour[]
     */
    public function getToursForPage(PFUser $user, $current_location)
    {
        $json = $this->getTourListJson($user);

        $tour_list = json_decode($json, true);
        if (! is_array($tour_list)) {
            return array();
        }

        $tour_folder = $this->getToursFolder($user);
        $this->extractWellFormedTourList($tour_list, $tour_folder);

        if (! $tour_list) {
            return array();
        }

        return $this->createToursFromList($tour_list, $current_location, $tour_folder);
    }

    protected function getTourListJson(PFUser $user)
    {
        $tour_folder = $this->getToursFolder($user);
        $config_file = $tour_folder . self::CUSTOM_TOURS_LIST_FILE;
        if (! is_dir($tour_folder) || ! file_exists($config_file)) {
            return '';
        }

        return file_get_contents($config_file);
    }

    private function getToursFolder(PFUser $user)
    {
        $user_lang = $user->getLocale();
        return ForgeConfig::get('sys_custom_incdir') . '/' . $user_lang . '/tour/';
    }

    private function extractWellFormedTourList(array &$tour_list, $tour_folder)
    {
        foreach ($tour_list as $key => $tour_data) {
            if (
                ! $this->isTourDataWellFormed($tour_data) ||
                ! $this->doesTourStepsFileExist($tour_folder, $tour_data['tour_name'])
            ) {
                unset($tour_list[$key]);
                continue;
            }
        }
    }

    private function isTourDataWellFormed($tour_data)
    {
        return (
            is_array($tour_data)
            && isset($tour_data['tour_name'])
            && is_string($tour_data['tour_name'])
            && isset($tour_data['url'])
            && is_string($tour_data['url'])
         );
    }

    private function doesTourStepsFileExist($tour_folder, $tour_name)
    {
        $file_name = $tour_folder . $tour_name . '.json';
        return file_exists($file_name);
    }

    private function createToursFromList($tour_list, $current_location, $tour_folder)
    {
        $tours = array();
        foreach ($tour_list as $listed_tour) {
            if (! $this->doesUrlMatchLocation($listed_tour['url'], $current_location)) {
                continue;
            }

            try {
                $tour = $this->getValidTour($listed_tour['tour_name'], $tour_folder);
            } catch (Exception $e) {
                continue;
            }

            $tours[] = $tour;
        }

        return $tours;
    }

    private function doesUrlMatchLocation($url, $current_location)
    {
        $current_location = strtolower(trim($current_location, '/'));
        $url              = strtolower(trim($url, '/'));

        if ($url === $current_location) {
            return true;
        }

        $this->replaceProjectPlaceHoldersInUrl($url, $current_location);
        $this->replaceAttributeValuesInUrl($url, $current_location);

        if ($url === $current_location) {
            return true;
        }

        return false;
    }

    /**
     * @see URL::getGroupIdFromUrl  It constructs the project_id by searching the url
     *                              for a project ID or shortname or other.
     */
    private function replaceProjectPlaceHoldersInUrl(&$url, $current_location)
    {
        $project_id  = $this->url_processor->getGroupIdFromUrl($current_location);
        try {
            $project = $this->project_manager->getValidProject($project_id);
        } catch (Project_NotFoundException $e) {
            return;
        }
        $project_name = $project->getUnixName();

        $project_placeholders = array(
            self::PLACEHOLDER_PROJECT_ID   => $project_id,
            self::PLACEHOLDER_PROJECT_NAME => $project_name
        );
        foreach ($project_placeholders as $placeholder => $value) {
            if (strpos($url, $placeholder) !== false) {
                $url = str_replace($placeholder, $value, $url);
            }
        }
    }

    private function replaceAttributeValuesInUrl(&$url, $current_location)
    {
        $placeholder = self::PLACEHOLDER_ATTRIBUTE_VALUE;

        if (strstr($url, $placeholder)) {
            $uri_parts  = explode('?', $current_location);
            $attributes = (isset($uri_parts[1])) ? explode('&', $uri_parts[1]) : array();
            foreach ($attributes as $attribute) {
                $key_value = explode('=', $attribute);
                $key       = preg_quote($key_value[0], '/');
                $url       = preg_replace("/$key=$placeholder/", $attribute, $url);
            }
        }
    }

    private function stepsAreValid(array $json_tour)
    {
        if (! isset($json_tour['steps']) || ! is_array($json_tour['steps'])) {
            return false;
        }

        return true;
    }

    /**
     * @return Tuleap_Tour
     * @throws Tuleap_UnknownTourException
     * @throws Tuleap_InvalidTourException
     */
    public function getTour(PFUser $user, $tour_name)
    {
        $tour_folder = $this->getToursFolder($user);

        $json      = $this->getTourListJson($user);
        $tour_list = json_decode($json, true);
        if (! is_array($tour_list)) {
            throw new Tuleap_UnknownTourException();
        }

        $is_enabled = false;
        foreach ($tour_list as $enabled_tour) {
            if (isset($enabled_tour['tour_name']) && $enabled_tour['tour_name'] == $tour_name) {
                $is_enabled = true;
            }
        }

        if (! $is_enabled) {
            throw new Tuleap_UnknownTourException();
        }

        return $this->getValidTour($tour_name, $tour_folder);
    }


    /**
     * @return Tuleap_Tour
     * @throws Tuleap_UnknownTourException
     * @throws Tuleap_InvalidTourException
     */
    private function getValidTour($tour_name, $tour_folder)
    {
        $file = $tour_folder . $tour_name . '.json';

        if (! file_exists($file)) {
            throw new Tuleap_UnknownTourException();
        }

        $json_tour = json_decode(file_get_contents($file), true);
        if (! is_array($json_tour)) {
            throw new Tuleap_InvalidTourException("Invalid tour file '$tour_name'");
        }

        if (! $this->stepsAreValid($json_tour)) {
            throw new Tuleap_InvalidTourException("Invalid steps for tour '$tour_name'");
        }

        return new Tuleap_Tour($tour_name, $json_tour['steps']);
    }
}
