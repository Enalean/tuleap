<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * User-editable parameters of the planning.
 */
class PlanningParameters {
    
    public $name;
    public $backlog_title;
    public $plan_title;
    public $backlog_tracker_id;
    public $planning_tracker_id;
    
    public static function fromArray(array $array) {
        $parameters = new PlanningParameters();
        
        $parameters->name                = PlanningParameters::get($array, 'name');
        $parameters->backlog_title       = PlanningParameters::get($array, 'backlog_title');
        $parameters->plan_title          = PlanningParameters::get($array, 'plan_title');
        $parameters->backlog_tracker_id  = PlanningParameters::get($array, 'backlog_tracker_id');
        $parameters->planning_tracker_id = PlanningParameters::get($array, 'planning_tracker_id');
        
        return $parameters;
    }
    
    private static function get($array, $key) {
        return array_key_exists($key, $array) ? $array[$key] : '';
    }
}
?>
