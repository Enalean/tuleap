<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class ServiceTracker extends Service {
    /**
     * Display header for service tracker
     *
     * @param string $title       The title
     * @param array  $breadcrumbs array of breadcrumbs (array of 'url' => string, 'title' => string)
     * @param array  $toolbar     array of toolbars (array of 'url' => string, 'title' => string)
     *
     * @return void
     */
    public function displayHeader($title, $breadcrumbs, $toolbar) {
        $GLOBALS['HTML']->includeCalendarScripts();
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }
    
    /**
     * Duplicate this service from the current project to another
     * 
     * @param int   $to_project_id  The target paroject Id
     * @param array $ugroup_mapping The ugroup mapping
     * 
     * @return void
     */
    public function duplicate($to_project_id, $ugroup_mapping) {
        $tracker_manager = $this->getTrackerManager();
        $tracker_manager->duplicate($this->project->getId(), $to_project_id, $ugroup_mapping);
    }
    
    /**
     * @return TrackerManager 
     */
    protected function getTrackerManager() {
        return new TrackerManager();
    }
    
    /**
     * Say if the service is allowed for the project
     *
     * @param Project $project
     *
     * @return bool
     */
    protected function isAllowed($project) {
        $plugin_manager = PluginManager::instance();
        $p = $plugin_manager->getPluginByName('tracker');
        if ($p && $plugin_manager->isPluginAvailable($p) && $p->isAllowed($project->getGroupId())) {
            return true;
        }
        return false;
    }
    
    /**
     * Say if the service is restricted
     *
     * @param Project $project
     *
     * @return bool
     */
    public function isRestricted() {
        $plugin_manager = PluginManager::instance();
        $p = $plugin_manager->getPluginByName('tracker');
        if ($p && $plugin_manager->isProjectPluginRestricted($p)) {
            return true;
        }
        return false;
    }
    
    /**
     * Trackers are cloned on project creation
     * 
     * @see Service::isInheritedOnDuplicate()
     * 
     * @return Boolean
     */
    public function isInheritedOnDuplicate() {
        return true;
    }
}
?>
