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

use Tuleap\AgileDashboard\Planning\Admin\PlanningEditURLEvent;

class Planning_PlanningAdminPresenter
{
    private $planning;
    /**
     * @var bool
     */
    public $is_planning_removal_dangerous;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var Planning|null
     */
    private $root_planning;

    public function __construct(
        EventManager $event_manager,
        Planning $planning,
        ?Planning $root_planning,
        bool $is_planning_removal_dangerous
    ) {
        $this->planning                      = $planning;
        $this->is_planning_removal_dangerous = $is_planning_removal_dangerous;
        $this->event_manager                 = $event_manager;
        $this->root_planning                 = $root_planning;
    }

    public function edit_url()
    {
        $event = new PlanningEditURLEvent(
            $this->planning,
            $this->root_planning
        );

        $this->event_manager->processEvent($event);

        return $event->getEditUrl();
    }

    public function delete_url()
    {
        return AGILEDASHBOARD_BASE_URL . '/?' . http_build_query([
            'group_id' => $this->planning->getGroupId(),
            'planning_id' => $this->planning->getId(),
            'action' => 'delete',
        ]);
    }

    public function name()
    {
        return $this->planning->getName();
    }

    public function delete_icon_path()
    {
        return $GLOBALS['HTML']->getImagePath('ic/bin_closed.png');
    }

    public function edit_action_label()
    {
        return dgettext('tuleap-agiledashboard', 'Edit');
    }

    public function edit_icon_path()
    {
        return $GLOBALS['HTML']->getImagePath('ic/edit.png');
    }

    public function li_class()
    {
        return '';
    }

    public function extra_message()
    {
        return '';
    }
}
