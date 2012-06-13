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

require_once 'Item.class.php';

class Planning_ArtifactPresenter {
    
    public function __construct(Planning_Item $planning_item, $css_classes = '') {
        $this->planning_item = $planning_item;
        $this->css_classes   = $css_classes;
    }
    
    public function getId() {
        return $this->planning_item->getId();
    }
    
    public function getTitle() {
        return $this->planning_item->getTitle();
    }
    
    public function getUrl() {
        return $this->planning_item->getEditUri();
    }
    
    public function getXRef() {
        return $this->planning_item->getXRef();
    }
    
    public function getEditUrl() {
        return $this->getUrl();
    }
    
    public function getArtifactId() {
        return $this->planning_item->getId();
    }
    
    public function getEditLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
    
    public function getCssClasses() {
        return trim($this->css_classes.' '.$this->getPlanningDraggableClass());
    }
    
    private function getPlanningDraggableClass() {
        if ($this->planning_item->isPlannifiable()) {
            return 'planning-draggable';
        }
    }
    
    public function allowedChildrenTypes() {
        $hierarchy_factory = Tracker_HierarchyFactory::instance();
        return $hierarchy_factory->getChildren($this->planning_item->getTracker()->getId());
    }
}
?>
