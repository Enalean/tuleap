<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

class AdminDelegation_Service //phpcs:ignore
{
    public const SHOW_PROJECT_ADMINS = 101;
    public const SHOW_PROJECTS       = 102;

    public static function getAllServices()
    {
        return array(self::SHOW_PROJECT_ADMINS, self::SHOW_PROJECTS);
    }

    public static function getAllLabels()
    {
        $a = array();
        foreach (self::getAllServices() as $service) {
            $a[] = self::getLabel($service);
        }
        return $a;
    }

    public static function getLabel($service)
    {
        switch ($service) {
            case self::SHOW_PROJECT_ADMINS:
                return dgettext('tuleap-admindelegation', 'See project administrators');
                break;
            case self::SHOW_PROJECTS:
                return dgettext('tuleap-admindelegation', 'See all projects');
                break;
        }
    }

    /**
     * Widget to const translator
     *
     * @param String $widget
     *
     * @return int
     */
    public static function getServiceFromWidget($widget)
    {
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
