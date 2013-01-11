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

class Planning_ListPresenter {
    public $group_id;
    
    public function __construct(array $plannings, $group_id) {
        $this->group_id = $group_id;
        $this->plannings = $plannings;
    }
    
    public function getPlannings() {
        return $this->plannings;
    }
    
    public function hasPlannings() {
        if (empty($this->plannings)) {
            return false;
        }
        return true;
    }
    
    public function getDeleteImagePath() {
        return $GLOBALS['HTML']->getImagePath('ic/bin_closed.png');
    }
    
    public function createPlanning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }
    
    public function getEditActionLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_edit');
    }
    
    public function getEditIconPath() {
        return $GLOBALS['HTML']->getImagePath('ic/edit.png');
    }
    
    public function adminTitle() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'Admin');
    }
}

?>
