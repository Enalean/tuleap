<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
class PlanningParameters
{
    public const NAME                = 'name';
    public const BACKLOG_TITLE       = 'backlog_title';
    public const PLANNING_TITLE      = 'plan_title';
    public const BACKLOG_TRACKER_IDS = 'backlog_tracker_ids';
    public const PLANNING_TRACKER_ID = 'planning_tracker_id';

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $backlog_title;
    /**
     * @var string
     */
    public $plan_title;
    /**
     * @psalm-var list<int>
     */
    public $backlog_tracker_ids = [];
    /**
     * @var string|null
     */
    public $planning_tracker_id;
    /**
     * @var string[]
     */
    public $priority_change_permission;

    public static function fromArray(array $array)
    {
        $parameters  = new PlanningParameters();
        $backlog_ids = self::get($array, self::BACKLOG_TRACKER_IDS);

        $parameters->name                       = self::get($array, self::NAME);
        $parameters->backlog_title              = self::get($array, self::BACKLOG_TITLE);
        $parameters->plan_title                 = self::get($array, self::PLANNING_TITLE);
        $parameters->backlog_tracker_ids        = ($backlog_ids) ? $backlog_ids : [];
        $parameters->planning_tracker_id        = self::get($array, self::PLANNING_TRACKER_ID);
        $parameters->priority_change_permission = self::get($array, PlanningPermissionsManager::PERM_PRIORITY_CHANGE);

        return $parameters;
    }

    private static function get($array, $key)
    {
        return array_key_exists($key, $array) ? $array[$key] : '';
    }
}
