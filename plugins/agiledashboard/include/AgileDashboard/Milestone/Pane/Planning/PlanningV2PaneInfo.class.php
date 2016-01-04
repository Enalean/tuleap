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

class AgileDashboard_Milestone_Pane_Planning_PlanningV2PaneInfo extends AgileDashboard_PaneInfo {
    const IDENTIFIER = 'planning-v2';

    /** @var Tracker */
    private $submilestone_tracker;

    /** @var string */
    private $theme_path;

    public function __construct(Planning_Milestone $milestone, $theme_path, Tracker $submilestone_tracker) {
        parent::__construct($milestone);
        $this->theme_path           = $theme_path;
        $this->submilestone_tracker = $submilestone_tracker;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    public function getTitle() {
        $title = $GLOBALS['Language']->getText('plugin_agiledashboard', 'milestone_planning_pane_title', $this->submilestone_tracker->getName());
        if (ForgeConfig::get('sys_showdeprecatedplanningv1')) {
            $title .= ' V2';
        }
        return $title;
    }

    protected function getIcon() {
        return $this->theme_path.'/images/planning.png';
    }

    protected function getIconTitle() {
        return $this->getTitle();
    }

}
