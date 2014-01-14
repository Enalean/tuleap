<?php
/**
 * Copyright (c) Enalean, 2012-2014. All Rights Reserved.
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

class Planning_AdminPresenter {

    public $group_id;

    private $root_planning_tracker_url;
    private $root_planning_name;
    private $planning_hierarchy = array();
    private $plannings = array();
    private $can_create_planning;

    public function __construct(array $plannings, $group_id, $can_create_planning, $root_planning_tracker_url, $root_planning_name, array $hierarchy) {
        $this->plannings = $plannings;
        $this->group_id  = $group_id;
        $this->can_create_planning = $can_create_planning;
        $this->root_planning_tracker_url = $root_planning_tracker_url;
        $this->root_planning_name = $root_planning_name;
        foreach ($hierarchy as $tracker) {
            $this->planning_hierarchy[] = $tracker->getName();
        }
    }

    public function plannings() {
        return $this->plannings;
    }

    public function has_plannings() {
        if (empty($this->plannings)) {
            return false;
        }
        return true;
    }

    public function createPlanning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }

    public function importTemplate() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_template');
    }

    public function exportTemplate() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'export_template');
    }

    public function adminTitle() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'Admin');
    }

    public function importExportSection() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_export_section');
    }

    public function planningSection() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_section');
    }

    public function can_create_planning() {
        return $this->can_create_planning;
    }

    public function cannot_create_planning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning');
    }

    public function cannot_create_planning_no_trackers() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_no_trackers');
    }

    public function cannot_create_planning_hierarchy() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_hierarchy', array(
            $this->getPlanningNamesHierarchy()
        ));
    }

    private function getPlanningNamesHierarchy() {
        if (count($this->planning_hierarchy) > 0) {
            return implode(', ', $this->planning_hierarchy);
        } else {
            return '';
        }
    }

    public function cannot_create_planning_config() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_config', array(
            $this->root_planning_name,
            $this->root_planning_tracker_url.'&func=admin-hierarchy',
        ));
    }
}

?>
