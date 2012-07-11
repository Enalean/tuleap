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
require_once TRACKER_BASE_DIR .'/Tracker/CardPresenter.class.php';
require_once 'ItemFieldPresenter.class.php';

class Planning_ItemPresenter implements Tracker_CardPresenter {
    
    /**
     * @var Planning_Item
     */
    private $planning_item;
    
    /**
     * @var string
     */
    private $css_classes;
    
    /**
     * @var Array
     */
    private $displayed_fields;
    /**
     * @param Planning_Item $planning_item The planning item to be presented.
     * @param string        $css_classes   The space-separated CSS classes to add to the main item HTML tag.
     */
    public function __construct(Planning_Item $planning_item, $css_classes = '') {
        $this->planning_item = $planning_item;
        $this->css_classes   = $css_classes;
        $this->details  = $GLOBALS['Language']->getText('plugin_cardwall', 'details');
        $this->displayed_fields   = array(Tracker_CardPresenter::REMAINING_EFFORT_FIELD_NAME,
                                          Tracker_CardPresenter::ASSIGNED_TO_FIELD_NAME,
                                          Tracker_CardPresenter::IMPEDIMENT_FIELD_NAME);
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
    
    public function getArtifact() {
        return $this->planning_item->getArtifact();
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
        return $this->planning_item->getAllowedChildrenTypes();
    }
    
    public function getFields() {
        $diplayed_fields_presenter = array();
        $user                      = UserManager::instance()->getCurrentUser();
        $form_element_factory      = Tracker_FormElementFactory::instance();
        $tracker_id                = $this->getArtifact()->getTracker()->getId();
        
        foreach ($this->displayed_fields as $diplayed_field_name) {
            $field = $form_element_factory->getUsedFieldByNameForUser(
                        $tracker_id,
                        $diplayed_field_name,
                        $user);
            if ($field) {
                $diplayed_fields_presenter[] = new Planning_ItemFieldPresenter($field, $this->getArtifact());
            }
        }
        return $diplayed_fields_presenter;
    }
    
    public function hasFields() {
        return count($this->getFields()) > 0;
    }
}
?>
