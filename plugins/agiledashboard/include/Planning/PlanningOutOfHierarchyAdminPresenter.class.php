<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

    public function __construct(
        EventManager $event_manager,
        Planning $planning,
        ?Planning $root_planning,
        bool $is_planning_removal_dangerous
    ) {
        parent::__construct($event_manager, $planning, $root_planning, $is_planning_removal_dangerous);

        if ($root_planning) {
            $this->root_planning_name = $root_planning->getName();
        } else {
            $this->root_planning_name = '';
        }

        $this->is_planning_removal_dangerous = $is_planning_removal_dangerous;
    }

    public function li_class()
    {
        return 'alert alert-error';
    }

    public function extra_message()
    {
        return sprintf(dgettext('tuleap-agiledashboard', 'This planning doesn\'t belong to root planning (%1$s) hierarchy. This configuration is not supported. You should remove this planning.'), $this->root_planning_name);
    }
}
