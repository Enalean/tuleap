<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class AdminDelegation_Service {
    const SHOW_PROJECT_ADMINS = 101;
    const SHOW_PROJECTS       = 102;
    
    public static function getAllServices() {
        return array(self::SHOW_PROJECT_ADMINS, self::SHOW_PROJECTS);
    }

    public static function getAllLabels() {
        $a = array();
        foreach (self::getAllServices() as $service) {
            $a[] = self::getLabel($service);
        }
        return $a;
    }

    public static function getLabel($service) {
        switch ($service) {
            case self::SHOW_PROJECT_ADMINS:
                return $GLOBALS['Language']->getText('plugin_admindelegation','service_SHOW_PROJECT_ADMINS');
                break;
            case self::SHOW_PROJECTS:
                return $GLOBALS['Language']->getText('plugin_admindelegation','service_SHOW_PROJECTS');
                break;
        }
    }

    /**
     * Widget to const translator
     *
     * @param String $widget
     *
     * @return Integer
     */
    public static function getServiceFromWidget($widget) {
        switch ($widget) {
            case 'admindelegation':
                return self::SHOW_PROJECT_ADMINS;
                break;
            case 'admindelegation_projects':
                return self::SHOW_PROJECTS;
                break;
        }
        return false;
    }
}

?>