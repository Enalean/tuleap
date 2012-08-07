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

class Planning_BacklogActionsPresenter {
    /**
     * @var Planning
     */
    private $planning;
    
    public $planning_redirect_parameter;

    public function __construct(Planning $planning, $planning_redirect_parameter) {
        $this->planning                = $planning;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
    }
    
    public function getBacklogTracker() {
        $tracker = $this->planning->getBacklogTracker();
        if ($tracker && $tracker->userCanView()) {
            return $tracker;
        }
        return null;
    }
    
    public function addLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'backlog_add');
    }
}

?>
