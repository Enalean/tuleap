<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Planning_PlanningOutOfHierarchyAdminPresenter extends Planning_PlanningAdminPresenter
{
    /**
     * @var bool
     */
    public $is_planning_removal_dangerous;
    private $root_planning_name;

    public function __construct(Planning $planning, $root_planning_name, bool $is_planning_removal_dangerous)
    {
        parent::__construct($planning, $is_planning_removal_dangerous);
        $this->root_planning_name            = $root_planning_name;
        $this->is_planning_removal_dangerous = $is_planning_removal_dangerous;
    }

    public function li_class()
    {
        return 'alert alert-error';
    }

    public function extra_message()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_out_of_hierarchy', array(
            $this->root_planning_name
        ));
    }
}
